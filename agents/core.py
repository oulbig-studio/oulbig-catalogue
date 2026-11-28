"""Briques communes : configuration, mémoire (état JSON), salon de discussion
entre agents, appel à Claude, WhatsApp Cloud API et Graph API Meta
(Instagram, Facebook, Facebook Ads).

Aucune clé n'est écrite dans le code : tout vient des variables d'environnement
(secrets GitHub Actions ou variables du serveur).
"""
from __future__ import annotations

import json
import os
import re
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

ICI = Path(__file__).resolve().parent
ETAT = ICI / "state"
ETAT.mkdir(exist_ok=True)


# --------------------------------------------------------------------------- #
# Configuration et mémoire
# --------------------------------------------------------------------------- #
def config() -> dict:
    with open(ICI / "config.json", encoding="utf-8") as f:
        return json.load(f)


def maintenant() -> str:
    return datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M UTC")


def lire(nom: str, defaut):
    p = ETAT / f"{nom}.json"
    if not p.exists():
        return defaut
    try:
        return json.loads(p.read_text(encoding="utf-8"))
    except json.JSONDecodeError:
        return defaut


def ecrire(nom: str, valeur) -> None:
    p = ETAT / f"{nom}.json"
    p.write_text(json.dumps(valeur, ensure_ascii=False, indent=1), encoding="utf-8")


def env(nom: str, obligatoire: bool = True) -> str:
    v = os.environ.get(nom, "").strip()
    if obligatoire and not v:
        raise RuntimeError(f"Variable d'environnement manquante : {nom}")
    return v


# --------------------------------------------------------------------------- #
# Salon : le fil de discussion partagé par les agents (relayé sur WhatsApp)
# --------------------------------------------------------------------------- #
SALON_MAX = 300


def salon_poster(de: str, texte: str, pour: str = "tous", donnees=None,
                 whatsapp: bool = True) -> dict:
    """Un agent publie un message dans le salon ; il part aussi sur WhatsApp."""
    msgs = lire("salon", [])
    m = {"id": (msgs[-1]["id"] + 1) if msgs else 1, "date": maintenant(),
         "de": de, "pour": pour, "texte": texte, "donnees": donnees}
    msgs.append(m)
    ecrire("salon", msgs[-SALON_MAX:])
    if whatsapp:
        wa_envoyer(f"{de} → {pour}\n{texte}")
    return m


def salon_recent(pour: str | None = None, n: int = 25) -> list[dict]:
    msgs = lire("salon", [])
    if pour:
        msgs = [m for m in msgs if m["pour"] in ("tous", pour) or m["de"] == pour]
    return msgs[-n:]


def salon_texte(msgs: list[dict]) -> str:
    if not msgs:
        return "(salon vide)"
    return "\n".join(f"[{m['date']}] {m['de']} → {m['pour']} : {m['texte']}" for m in msgs)


# --------------------------------------------------------------------------- #
# Propositions en attente de validation (publications, budgets pub…)
# --------------------------------------------------------------------------- #
def proposer(agent: str, action: str, resume: str, params: dict) -> dict:
    props = lire("propositions", [])
    p = {"id": max([x["id"] for x in props], default=0) + 1, "date": maintenant(),
         "agent": agent, "action": action, "resume": resume, "params": params,
         "statut": "en_attente"}
    props.append(p)
    ecrire("propositions", props[-200:])
    salon_poster(agent, f"📝 Proposition #{p['id']} : {resume}\n"
                        f"Réponds « OK {p['id']} » pour valider ou « NON {p['id']} ».",
                 pour="Patron")
    return p


# --------------------------------------------------------------------------- #
# Claude
# --------------------------------------------------------------------------- #
_client = None


def _claude():
    global _client
    if _client is None:
        import anthropic  # import tardif : le serveur démarre même sans la lib
        _client = anthropic.Anthropic()  # lit ANTHROPIC_API_KEY
    return _client


def demander(systeme: str, question: str, *, web: bool = False,
             effort: str = "medium", max_tokens: int = 16000) -> str:
    """Pose une question à Claude, avec recherche web si `web=True`.
    Renvoie le texte final de la réponse."""
    cfg = config().get("claude", {})
    outils = []
    if web:
        outils.append({"type": "web_search_20260209", "name": "web_search",
                       "max_uses": cfg.get("recherches_max", 8)})
    messages = [{"role": "user", "content": question}]
    for _ in range(6):  # reprises éventuelles après pause_turn
        kwargs = dict(
            model=cfg.get("modele", "claude-opus-5-5"),
            max_tokens=max_tokens,
            system=systeme,
            messages=messages,
            output_config={"effort": effort},
            betas=["server-side-fallback-2026-07-01"],
            fallbacks="default",  # si un refus survient, un autre modèle reprend
        )
        if outils:
            kwargs["tools"] = outils
        rep = _claude().beta.messages.create(**kwargs)
        if rep.stop_reason == "pause_turn":
            messages.append({"role": "assistant", "content": rep.content})
            continue
        if rep.stop_reason == "refusal":
            return ""
        return "".join(b.text for b in rep.content if b.type == "text").strip()
    return ""


def extraire_json(texte: str, defaut):
    """Récupère le premier bloc JSON (objet ou liste) d'une réponse."""
    m = re.search(r"```(?:json)?\s*(.*?)```", texte, re.S)
    brut = m.group(1) if m else texte
    paires = sorted((("{", "}"), ("[", "]")),
                    key=lambda p: brut.find(p[0]) if p[0] in brut else len(brut))
    for ouvrant, fermant in paires:  # le premier délimiteur rencontré d'abord
        i, j = brut.find(ouvrant), brut.rfind(fermant)
        if i != -1 and j > i:
            try:
                return json.loads(brut[i:j + 1])
            except json.JSONDecodeError:
                pass
    return defaut


# --------------------------------------------------------------------------- #
# HTTP
# --------------------------------------------------------------------------- #
def http(methode: str, url: str, *, params: dict | None = None,
         corps: dict | None = None, entetes: dict | None = None) -> dict:
    if params:
        url += ("&" if "?" in url else "?") + urllib.parse.urlencode(params)
    data = None
    h = dict(entetes or {})
    if corps is not None:
        data = json.dumps(corps).encode()
        h["Content-Type"] = "application/json"
    req = urllib.request.Request(url, data=data, headers=h, method=methode)
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            txt = r.read().decode() or "{}"
            return json.loads(txt)
    except urllib.error.HTTPError as e:
        detail = e.read().decode(errors="replace")[:800]
        raise RuntimeError(f"HTTP {e.code} sur {url.split('?')[0]} : {detail}") from None


# --------------------------------------------------------------------------- #
# WhatsApp Cloud API
# --------------------------------------------------------------------------- #
def wa_envoyer(texte: str, destinataire: str | None = None) -> bool:
    """Envoie un message WhatsApp au patron (ou à `destinataire`).
    Sans configuration WhatsApp, affiche simplement le message."""
    token = env("WHATSAPP_TOKEN", False)
    phone_id = env("WHATSAPP_PHONE_ID", False)
    dest = destinataire or env("WHATSAPP_PATRON", False)
    if not (token and phone_id and dest):
        print("[WhatsApp désactivé]", texte)
        return False
    url = f"https://graph.facebook.com/{graph_version()}/{phone_id}/messages"
    auth = {"Authorization": f"Bearer {token}"}
    for morceau in _decouper(texte, 3800):
        try:
            http("POST", url, entetes=auth, corps={
                "messaging_product": "whatsapp", "to": dest, "type": "text",
                "text": {"body": morceau, "preview_url": True}})
        except RuntimeError as e:
            # Hors fenêtre de 24 h : WhatsApp exige un modèle (template) approuvé.
            modele = config().get("whatsapp", {}).get("modele_relance")
            if "131047" in str(e) and modele:
                http("POST", url, entetes=auth, corps={
                    "messaging_product": "whatsapp", "to": dest, "type": "template",
                    "template": {"name": modele, "language": {"code": "fr"}}})
                _en_attente_wa(texte)
                return True
            print("[WhatsApp erreur]", e)
            return False
    return True


def _en_attente_wa(texte: str) -> None:
    """Garde les messages non remis ; ils partent quand le patron répond."""
    file = lire("wa_en_attente", [])
    file.append(texte)
    ecrire("wa_en_attente", file[-50:])


def wa_vider_attente() -> None:
    file = lire("wa_en_attente", [])
    if file:
        ecrire("wa_en_attente", [])
        wa_envoyer("📬 Messages en attente :\n\n" + "\n\n".join(file))


def _decouper(t: str, n: int):
    while t:
        yield t[:n]
        t = t[n:]


# --------------------------------------------------------------------------- #
# Graph API Meta (Instagram, Facebook, Ads)
# --------------------------------------------------------------------------- #
def graph_version() -> str:
    return config().get("meta", {}).get("graph_version", "v23.0")


def graph(methode: str, chemin: str, jeton: str | None = None, **params) -> dict:
    jeton = jeton or env("META_PAGE_TOKEN")
    url = f"https://graph.facebook.com/{graph_version()}/{chemin.lstrip('/')}"
    params["access_token"] = jeton
    if methode == "GET":
        return http("GET", url, params=params)
    # POST : paramètres en formulaire, comme le veut la Graph API
    data = urllib.parse.urlencode(
        {k: (json.dumps(v) if isinstance(v, (dict, list)) else v) for k, v in params.items()}
    ).encode()
    req = urllib.request.Request(url, data=data, method="POST")
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.loads(r.read().decode() or "{}")
    except urllib.error.HTTPError as e:
        raise RuntimeError(f"Graph {e.code} sur {chemin} : "
                           f"{e.read().decode(errors='replace')[:800]}") from None


def catalogue(n: int = 40) -> list[dict]:
    """Derniers titres OULBIG (docs/catalogue.json), pour nourrir le marketing."""
    p = ICI.parent / "docs" / "catalogue.json"
    if not p.exists():
        return []
    items = json.loads(p.read_text(encoding="utf-8")).get("items", [])
    items.sort(key=lambda x: x.get("published", ""), reverse=True)
    return [{"titre": i["title"], "type": i["type"], "langue": i["lang"],
             "date": i.get("published"), "image": i.get("thumb"),
             "lien": (i.get("youtube") or {}).get("url")} for i in items[:n]]


def pause(s: float) -> None:
    time.sleep(s)
