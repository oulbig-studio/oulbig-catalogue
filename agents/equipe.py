"""L'équipe d'agents OULBIG.

  💬 Réponse-BD       Instagram + Facebook : un commentaire avec un mot-clé
                      (« BD », « je veux »…) reçoit la BD en message privé.
  📸 Insta-Deals      Instagram : opportunités de vente, d'achat, de deals,
                      de collaborations.
  📸 Insta-Marketing  Instagram : prépare les publications des marques.
  📘 FB-Deals         Facebook : opportunités de vente, d'achat et de deals.
  📘 FB-Marketing     Facebook : prépare les publications des marques.
  💰 FB-Ads           Facebook Ads : analyse les campagnes, propose
                      pauses / budgets / nouvelles idées de pubs.
  🔭 Tendances        Tendances : sujets de livres demandés, applications,
                      autres sujets porteurs pour investir.
  🎯 Coordinateur     Reçoit tes messages WhatsApp, anime les réunions,
                      exécute ce que tu valides.

Les agents se parlent dans un « salon » commun (agents/state/salon.json),
recopié sur WhatsApp. Tout ce qui publie ou dépense de l'argent passe par
une proposition que tu valides par « OK n » sur WhatsApp (sauf si tu
actives l'auto-publication dans config.json).
"""
from __future__ import annotations

import json
from datetime import datetime, timedelta, timezone

from . import core
from .core import (config, demander, env, extraire_json, graph, lire, ecrire,
                   proposer, salon_poster, salon_recent, salon_texte)

BD, IG_DEALS, IG_MKT = "💬 Réponse-BD", "📸 Insta-Deals", "📸 Insta-Marketing"
FB_DEALS, FB_MKT, ADS = "📘 FB-Deals", "📘 FB-Marketing", "💰 FB-Ads"
TENDANCES, COORD = "🔭 Tendances", "🎯 Coordinateur"


def _marques() -> str:
    m = config().get("marques", [])
    return "\n".join(f"- {x['nom']} : {x['description']} ({x.get('lien', '')})" for x in m)


def _persona(nom: str, role: str) -> str:
    return (f"Tu es {nom}, un agent de l'équipe marketing d'OULBIG Studio. Rôle : {role}\n"
            f"Marques de l'équipe :\n{_marques()}\n"
            "Tu travailles avec d'autres agents dans un salon partagé et tu rends compte "
            "au patron sur WhatsApp. Réponds en français, de façon concrète et courte "
            "(lisible sur un téléphone). Ne fabrique jamais de chiffres, de liens ou de "
            "contacts : cite seulement ce que tu as trouvé, avec le lien.")


# =========================================================================== #
# 💬 Réponse-BD : commentaire → BD en message privé
# =========================================================================== #
def _bd_pour(texte: str) -> dict | None:
    cfg = config()["reponse_bd"]
    t = texte.lower()
    if not any(k.lower() in t for k in cfg["mots_cles"]):
        return None
    for bd in cfg["bds"]:
        if any(k.lower() in t for k in bd.get("mots_cles", [])):
            return bd
    return cfg["bds"][0] if cfg["bds"] else None


def traiter_commentaire(plateforme: str, comment_id: str, texte: str,
                        auteur: str = "", date_iso: str | None = None) -> bool:
    """Répond à un commentaire s'il réclame une BD. Renvoie True si envoyé."""
    deja = set(lire("bd_traites", []))
    if comment_id in deja:
        return False
    cfg = config()["reponse_bd"]
    if date_iso:  # la réponse privée n'est possible que 7 jours après le commentaire
        try:
            d = datetime.fromisoformat(date_iso.replace("+0000", "+00:00"))
            if datetime.now(timezone.utc) - d > timedelta(days=7):
                return False
        except ValueError:
            pass
    bd = _bd_pour(texte)
    if not bd:
        return False
    page_id = env("META_PAGE_ID")
    message = cfg["message_prive"].format(prenom=auteur or "", titre=bd["titre"], lien=bd["lien"])
    if cfg.get("personnaliser_avec_claude"):
        perso = demander(_persona(BD, "répondre chaleureusement aux fans qui demandent une BD."),
                         f"Commentaire de {auteur} : « {texte} »\nÉcris le message privé (3 phrases max) "
                         f"qui offre la BD « {bd['titre']} » avec ce lien exact : {bd['lien']}. "
                         "Réponds seulement avec le message.", effort="low", max_tokens=2000)
        if bd["lien"] in perso:
            message = perso
    # Réponse privée (Messenger / Instagram Direct) au commentaire
    graph("POST", f"{page_id}/messages", recipient={"comment_id": comment_id},
          message={"text": message})
    # Petite réponse publique sous le commentaire
    publique = cfg.get("reponse_publique")
    if publique:
        chemin = f"{comment_id}/replies" if plateforme == "instagram" else f"{comment_id}/comments"
        try:
            graph("POST", chemin, message=publique.format(prenom=auteur or ""))
        except RuntimeError as e:
            print("Réponse publique impossible :", e)
    deja.add(comment_id)
    ecrire("bd_traites", sorted(deja)[-5000:])
    stats = lire("bd_stats", {})
    stats[bd["titre"]] = stats.get(bd["titre"], 0) + 1
    ecrire("bd_stats", stats)
    return True


def agent_reponse_bd() -> str:
    """Parcourt les derniers posts Instagram et Facebook (mode sans webhook)."""
    cfg = config()["reponse_bd"]
    envoyes = []
    if cfg.get("instagram", True) and env("META_IG_USER_ID", False):
        ig = env("META_IG_USER_ID")
        for media in graph("GET", f"{ig}/media", fields="id", limit=cfg["posts_a_surveiller"]).get("data", []):
            for c in graph("GET", f"{media['id']}/comments",
                           fields="id,text,username,timestamp", limit=50).get("data", []):
                if traiter_commentaire("instagram", c["id"], c.get("text", ""),
                                       c.get("username", ""), c.get("timestamp")):
                    envoyes.append(f"@{c.get('username', '?')} (Instagram)")
    if cfg.get("facebook", True) and env("META_PAGE_ID", False):
        page = env("META_PAGE_ID")
        for post in graph("GET", f"{page}/posts", fields="id", limit=cfg["posts_a_surveiller"]).get("data", []):
            for c in graph("GET", f"{post['id']}/comments",
                           fields="id,message,from,created_time", limit=50).get("data", []):
                if traiter_commentaire("facebook", c["id"], c.get("message", ""),
                                       (c.get("from") or {}).get("name", ""), c.get("created_time")):
                    envoyes.append(f"{(c.get('from') or {}).get('name', '?')} (Facebook)")
    if envoyes:
        salon_poster(BD, f"📚 BD envoyée à {len(envoyes)} personne(s) : " + ", ".join(envoyes[:15]),
                     pour=IG_MKT)
    return f"{len(envoyes)} BD envoyée(s)"


# =========================================================================== #
# 📸 / 📘 Deals : opportunités de vente, d'achat, de deals
# =========================================================================== #
def _hashtags_instagram() -> str:
    """Posts récents sur les hashtags suivis (API Instagram Hashtag Search)."""
    ig = env("META_IG_USER_ID", False)
    if not ig:
        return "(Instagram non connecté)"
    lignes = []
    for tag in config()["deals"]["hashtags_instagram"][:5]:  # limite Meta : 30 hashtags / 7 jours
        try:
            hid = graph("GET", "ig_hashtag_search", user_id=ig, q=tag)["data"][0]["id"]
            posts = graph("GET", f"{hid}/top_media", user_id=ig, limit=8,
                          fields="caption,permalink,like_count,comments_count").get("data", [])
        except (RuntimeError, IndexError, KeyError) as e:
            lignes.append(f"#{tag} : indisponible ({str(e)[:80]})")
            continue
        for p in posts:
            lignes.append(f"#{tag} | {p.get('like_count', 0)}❤ {p.get('comments_count', 0)}💬 | "
                          f"{(p.get('caption') or '')[:160]!r} | {p.get('permalink')}")
    return "\n".join(lignes) or "(rien)"


def _agent_deals(nom: str, plateforme: str) -> str:
    cfg = config()["deals"]
    vus = lire(f"deals_vus_{plateforme}", [])
    contexte = _hashtags_instagram() if plateforme == "instagram" else ""
    reponse = demander(
        _persona(nom, f"trouver sur {plateforme} et sur le web des opportunités concrètes de "
                      "vente, d'achat, de deals, de partenariats et de collaborations "
                      "pour nos marques."),
        f"Cherche les meilleures opportunités du moment sur {plateforme} pour nos marques. "
        f"Zones visées : {', '.join(cfg['zones'])}. Types : {', '.join(cfg['types'])}.\n"
        f"Données Instagram récentes :\n{contexte}\n\n"
        f"Déjà signalées (ne pas répéter) : {json.dumps(vus[-40:], ensure_ascii=False)}\n"
        f"Échanges récents de l'équipe :\n{salon_texte(salon_recent(nom, 10))}\n\n"
        "Rends au plus 6 opportunités, en JSON :\n"
        '```json\n[{"titre": "...", "type": "vente|achat|deal|collab", "lien": "...", '
        '"pourquoi": "...", "message_d_approche": "...", "score": 1-10}]\n```',
        web=True, effort="high")
    opps = [o for o in extraire_json(reponse, []) if isinstance(o, dict) and o.get("lien") not in vus]
    opps.sort(key=lambda o: -int(o.get("score", 0) or 0))
    if not opps:
        salon_poster(nom, "Rien de nouveau et de sérieux aujourd'hui.", pour="Patron")
        return "0 opportunité"
    ecrire(f"deals_vus_{plateforme}", (vus + [o.get("lien") for o in opps])[-500:])
    texte = "\n\n".join(f"{i}. [{o.get('type')}] {o.get('titre')} ({o.get('score')}/10)\n"
                        f"   {o.get('pourquoi')}\n   🔗 {o.get('lien')}\n"
                        f"   ✉️ « {o.get('message_d_approche')} »" for i, o in enumerate(opps, 1))
    salon_poster(nom, f"🔎 Opportunités {plateforme} :\n\n{texte}", pour="Patron", donnees=opps)
    meilleures = [o for o in opps if int(o.get("score", 0) or 0) >= 8]
    if meilleures:
        salon_poster(nom, "Ces pistes méritent peut-être une pub ciblée : "
                     + "; ".join(o["titre"] for o in meilleures), pour=ADS, whatsapp=False)
    return f"{len(opps)} opportunité(s)"


def agent_insta_deals() -> str:
    return _agent_deals(IG_DEALS, "instagram")


def agent_fb_deals() -> str:
    return _agent_deals(FB_DEALS, "facebook")


# =========================================================================== #
# 📸 / 📘 Marketing : publications des marques
# =========================================================================== #
def _agent_marketing(nom: str, plateforme: str) -> str:
    cfg = config()["marketing"]
    deja = lire(f"publies_{plateforme}", [])
    reponse = demander(
        _persona(nom, f"publier et faire la promotion de nos marques sur {plateforme}."),
        f"Prépare {cfg['posts_par_jour']} publication(s) {plateforme} pour aujourd'hui.\n"
        f"Ligne éditoriale : {cfg['ligne_editoriale']}\n"
        f"Derniers titres OULBIG disponibles (avec image) :\n"
        f"{json.dumps(core.catalogue(25), ensure_ascii=False)}\n"
        f"Déjà publiés récemment (éviter) : {json.dumps(deja[-30:], ensure_ascii=False)}\n"
        f"Ce que disent les autres agents (tendances, BD, deals) :\n"
        f"{salon_texte(salon_recent(nom, 15))}\n\n"
        "Utilise seulement des images de la liste (champ image). Réponds en JSON :\n"
        '```json\n[{"marque": "...", "texte": "légende complète avec appel à l\'action et '
        'hashtags", "image": "url", "lien": "url", "heure_conseillee": "HH:MM"}]\n```',
        effort="medium")
    posts = [p for p in extraire_json(reponse, []) if isinstance(p, dict) and p.get("texte")]
    action = "publier_instagram" if plateforme == "instagram" else "publier_facebook"
    for p in posts:
        params = {"texte": p["texte"], "image": p.get("image"), "lien": p.get("lien")}
        if cfg.get("auto_publier"):
            executer_action(action, params)
            salon_poster(nom, f"✅ Publié sur {plateforme} : {p['texte'][:120]}…", pour="Patron")
        else:
            proposer(nom, action, f"Post {plateforme} ({p.get('marque', '')}, "
                                  f"{p.get('heure_conseillee', '')}) :\n{p['texte']}\n🖼 {p.get('image')}",
                     params)
        deja.append(p.get("image") or p["texte"][:60])
    ecrire(f"publies_{plateforme}", deja[-200:])
    return f"{len(posts)} post(s) préparé(s)"


def agent_insta_marketing() -> str:
    return _agent_marketing(IG_MKT, "instagram")


def agent_fb_marketing() -> str:
    return _agent_marketing(FB_MKT, "facebook")


# =========================================================================== #
# 💰 FB-Ads : Facebook / Instagram Ads
# =========================================================================== #
def agent_fb_ads() -> str:
    compte = env("META_AD_ACCOUNT_ID", False)
    if not compte:
        salon_poster(ADS, "Pas de compte pub configuré (META_AD_ACCOUNT_ID).", pour="Patron")
        return "non configuré"
    jeton = env("META_ADS_TOKEN", False) or env("META_PAGE_TOKEN")
    acte = compte if compte.startswith("act_") else f"act_{compte}"
    campagnes = graph("GET", f"{acte}/campaigns", jeton, limit=50,
                      fields="id,name,status,objective,daily_budget,lifetime_budget").get("data", [])
    stats = graph("GET", f"{acte}/insights", jeton, level="campaign", date_preset="last_7d",
                  fields="campaign_id,campaign_name,spend,impressions,reach,clicks,ctr,cpc,cpm,actions",
                  limit=100).get("data", [])
    regles = config()["ads"]
    reponse = demander(
        _persona(ADS, "gérer les campagnes Facebook/Instagram Ads pour un maximum de résultats "
                      "par euro dépensé."),
        f"Campagnes : {json.dumps(campagnes, ensure_ascii=False)}\n"
        f"Résultats 7 derniers jours : {json.dumps(stats, ensure_ascii=False)}\n"
        f"Règles du patron : {json.dumps(regles, ensure_ascii=False)}\n"
        f"Messages de l'équipe (deals, tendances, posts) :\n{salon_texte(salon_recent(ADS, 15))}\n\n"
        "1) Bilan en 4 lignes. 2) Actions sur les campagnes existantes (pause, reprise, "
        "budget journalier en centimes ; jamais au-dessus du budget max). 3) Une ou deux idées "
        "de nouvelles pubs à partir des tendances et des deals. Réponds en JSON :\n"
        '```json\n{"bilan": "...", "actions": [{"action": "pause|reprendre|budget", '
        '"campagne_id": "...", "campagne": "...", "budget_jour_centimes": 0, "raison": "..."}], '
        '"idees": ["..."]}\n```', effort="high")
    r = extraire_json(reponse, {})
    salon_poster(ADS, f"📊 Bilan pubs (7 j) :\n{r.get('bilan', reponse[:1500])}", pour="Patron")
    budget_max = int(regles["budget_jour_max_centimes"])
    for a in r.get("actions", []):
        if a.get("action") == "budget":
            a["budget_jour_centimes"] = min(int(a.get("budget_jour_centimes") or 0), budget_max)
        proposer(ADS, f"ads_{a.get('action')}",
                 f"{a.get('action')} « {a.get('campagne')} »"
                 + (f" → {a['budget_jour_centimes'] / 100:.2f} €/jour" if a.get("action") == "budget" else "")
                 + f" — {a.get('raison')}", a)
    if r.get("idees"):
        salon_poster(ADS, "💡 Idées de pubs :\n- " + "\n- ".join(r["idees"]), pour="tous")
    return f"{len(r.get('actions', []))} action(s) proposée(s)"


# =========================================================================== #
# 🔭 Tendances : livres, applications, sujets pour investir
# =========================================================================== #
def agent_tendances() -> str:
    cfg = config()["tendances"]
    reponse = demander(
        _persona(TENDANCES, "repérer les tendances et les demandes du marché : sujets de livres "
                            "recherchés, applications à créer, autres sujets intéressants pour "
                            "investir."),
        f"Fais une veille des tendances actuelles (date : {core.maintenant()}). Sources à privilégier : "
        f"{', '.join(cfg['sources'])}. Marchés : {', '.join(cfg['marches'])}.\n"
        "Trois rubriques : livres (sujets demandés, niches peu servies), applications "
        "(problèmes que les gens veulent résoudre, apps en forte croissance), investissement "
        "(autres sujets porteurs). Pour chaque idée : la preuve de la demande (avec lien), "
        "la concurrence, et ce qu'OULBIG Studio pourrait en faire. Réponds en JSON :\n"
        '```json\n{"livres": [{"sujet": "...", "preuve": "...", "lien": "...", "concurrence": '
        '"faible|moyenne|forte", "idee_oulbig": "...", "score": 1-10}], "applications": [...], '
        '"investissement": [...]}\n```', web=True, effort="high")
    r = extraire_json(reponse, {})
    if not r:
        salon_poster(TENDANCES, reponse[:3500] or "Veille impossible aujourd'hui.", pour="Patron")
        return "texte brut"
    historique = lire("tendances", [])
    historique.append({"date": core.maintenant(), **r})
    ecrire("tendances", historique[-60:])
    blocs = []
    for rubrique, emoji in (("livres", "📚"), ("applications", "📱"), ("investissement", "📈")):
        idees = sorted(r.get(rubrique, []), key=lambda x: -int(x.get("score", 0) or 0))[:cfg["par_rubrique"]]
        if idees:
            blocs.append(f"{emoji} {rubrique.upper()}\n" + "\n".join(
                f"• {x.get('sujet')} ({x.get('score')}/10, concurrence {x.get('concurrence')})\n"
                f"  {x.get('preuve')} 🔗 {x.get('lien')}\n  → {x.get('idee_oulbig')}" for x in idees))
    salon_poster(TENDANCES, "Veille du jour :\n\n" + "\n\n".join(blocs), pour="Patron", donnees=r)
    salon_poster(TENDANCES, "Sujets chauds à exploiter dans vos posts et pubs : " + "; ".join(
        x.get("sujet", "") for k in ("livres", "applications") for x in r.get(k, [])[:3]),
        pour="tous", whatsapp=False)
    return "veille publiée"


# =========================================================================== #
# Exécution des propositions validées
# =========================================================================== #
def executer_action(action: str, p: dict) -> str:
    if action == "publier_instagram":
        ig = env("META_IG_USER_ID")
        if not p.get("image"):
            raise RuntimeError("Instagram exige une image")
        cont = graph("POST", f"{ig}/media", image_url=p["image"], caption=p["texte"])
        for _ in range(10):  # attendre que le média soit prêt
            if graph("GET", cont["id"], fields="status_code").get("status_code") == "FINISHED":
                break
            core.pause(3)
        res = graph("POST", f"{ig}/media_publish", creation_id=cont["id"])
        return f"publié (id {res.get('id')})"
    if action == "publier_facebook":
        page = env("META_PAGE_ID")
        if p.get("image"):
            res = graph("POST", f"{page}/photos", url=p["image"],
                        caption=p["texte"] + (f"\n{p['lien']}" if p.get("lien") else ""))
        else:
            res = graph("POST", f"{page}/feed", message=p["texte"], link=p.get("lien") or "")
        return f"publié (id {res.get('post_id') or res.get('id')})"
    if action.startswith("ads_"):
        jeton = env("META_ADS_TOKEN", False) or env("META_PAGE_TOKEN")
        cid = p["campagne_id"]
        if action == "ads_pause":
            graph("POST", cid, jeton, status="PAUSED")
            return "campagne en pause"
        if action == "ads_reprendre":
            graph("POST", cid, jeton, status="ACTIVE")
            return "campagne relancée"
        if action == "ads_budget":
            montant = min(int(p["budget_jour_centimes"]),
                          int(config()["ads"]["budget_jour_max_centimes"]))
            graph("POST", cid, jeton, daily_budget=montant)
            return f"budget mis à {montant / 100:.2f} €/jour"
    raise RuntimeError(f"Action inconnue : {action}")


def decider(num: int, accepte: bool) -> str:
    props = lire("propositions", [])
    p = next((x for x in props if x["id"] == num), None)
    if not p:
        return f"Proposition #{num} introuvable."
    if p["statut"] != "en_attente":
        return f"Proposition #{num} déjà traitée ({p['statut']})."
    if not accepte:
        p["statut"] = "refusee"
        ecrire("propositions", props)
        salon_poster(COORD, f"Proposition #{num} refusée par le patron.", pour=p["agent"], whatsapp=False)
        return f"❌ #{num} refusée."
    try:
        res = executer_action(p["action"], p["params"])
        p["statut"], p["resultat"] = "faite", res
    except RuntimeError as e:
        p["statut"], p["resultat"] = "erreur", str(e)
        res = f"erreur : {e}"
    ecrire("propositions", props)
    return f"#{num} → {res}"


# =========================================================================== #
# 🎯 Coordinateur : réunion d'équipe et messages WhatsApp
# =========================================================================== #
AGENTS = {
    "bd": (BD, agent_reponse_bd),
    "insta-deals": (IG_DEALS, agent_insta_deals),
    "insta-marketing": (IG_MKT, agent_insta_marketing),
    "fb-deals": (FB_DEALS, agent_fb_deals),
    "fb-marketing": (FB_MKT, agent_fb_marketing),
    "fb-ads": (ADS, agent_fb_ads),
    "tendances": (TENDANCES, agent_tendances),
}

ROLES = {
    BD: "envoyer les BD aux fans qui les demandent en commentaire",
    IG_DEALS: "trouver des opportunités de vente, d'achat et de deals sur Instagram",
    IG_MKT: "publier et promouvoir nos marques sur Instagram",
    FB_DEALS: "trouver des opportunités de vente, d'achat et de deals sur Facebook",
    FB_MKT: "publier et promouvoir nos marques sur Facebook",
    ADS: "piloter les publicités Facebook/Instagram Ads",
    TENDANCES: "repérer les tendances (livres, applications, investissements)",
}


def reunion() -> str:
    """Chaque agent lit le salon et réagit : c'est là qu'ils discutent entre eux."""
    for nom, role in ROLES.items():
        if nom == BD:
            continue
        avis = demander(
            _persona(nom, role),
            f"Réunion d'équipe. Voici le salon :\n{salon_texte(salon_recent(None, 30))}\n\n"
            "En 3 lignes max : ce que tu retiens pour ton travail, et une demande précise à "
            "un autre agent (nomme-le). Si tu n'as rien d'utile, réponds seulement « RAS ».",
            effort="low", max_tokens=3000)
        if avis and avis.strip().upper() != "RAS":
            salon_poster(nom, avis, pour="équipe")
    synthese = demander(_persona(COORD, "coordonner l'équipe et résumer pour le patron."),
                        f"Salon :\n{salon_texte(salon_recent(None, 20))}\n\n"
                        "Fais la synthèse de la réunion pour le patron : 3 décisions à prendre "
                        "et le plan de la journée. 8 lignes max.", effort="medium", max_tokens=4000)
    salon_poster(COORD, f"🗂 Synthèse de réunion :\n{synthese}", pour="Patron")
    return "réunion terminée"


AIDE = """🎯 Commandes :
• OK 12 / NON 12 — valider ou refuser la proposition n°12
• lance tendances | bd | insta-deals | insta-marketing | fb-deals | fb-marketing | fb-ads | tout
• réunion — les agents discutent et te font une synthèse
• attente — propositions en attente
• @tendances <question> (ou @fb-ads, @insta-deals…) — parler à un agent
• n'importe quel autre message — le coordinateur répond ou passe le relais"""


def commande(texte: str) -> str:
    """Traite un message WhatsApp du patron et renvoie la réponse."""
    t = texte.strip()
    bas = t.lower()
    core.wa_vider_attente()
    mots = bas.split()
    if mots and mots[0] in ("ok", "oui", "non", "no") and len(mots) > 1 and mots[1].lstrip("#").isdigit():
        return decider(int(mots[1].lstrip("#")), mots[0] in ("ok", "oui"))
    if bas in ("aide", "help", "?", "menu"):
        return AIDE
    if bas in ("attente", "propositions"):
        att = [p for p in lire("propositions", []) if p["statut"] == "en_attente"]
        return "\n\n".join(f"#{p['id']} {p['agent']} : {p['resume'][:300]}" for p in att) or "Rien en attente."
    if bas.startswith(("réunion", "reunion")):
        return reunion()
    if bas.startswith("lance"):
        cible = bas.split(maxsplit=1)[1].strip() if len(mots) > 1 else ""
        return lancer(cible)
    if bas.startswith("@"):
        cle, _, question = t[1:].partition(" ")
        if cle.lower() in AGENTS:
            nom = AGENTS[cle.lower()][0]
            rep = demander(_persona(nom, ROLES[nom]),
                           f"Le patron te demande : {question}\n\nSalon récent :\n"
                           f"{salon_texte(salon_recent(nom, 15))}", web=True, effort="medium")
            salon_poster(nom, rep, pour="Patron", whatsapp=False)
            return f"{nom} :\n{rep}"
    # Message libre : le coordinateur répond et peut lancer des agents
    salon_poster("Patron", t, pour="équipe", whatsapp=False)
    rep = demander(
        _persona(COORD, "coordonner l'équipe d'agents et répondre au patron."),
        f"Agents disponibles : {json.dumps({k: ROLES[v[0]] for k, v in AGENTS.items()}, ensure_ascii=False)}\n"
        f"Salon récent :\n{salon_texte(salon_recent(None, 20))}\n\n"
        f"Message du patron : « {t} »\n"
        'Réponds en JSON : {"reponse": "...", "lancer": ["cle_agent", ...]} '
        "(lancer = agents à faire travailler maintenant pour répondre à la demande, souvent vide).",
        effort="medium", max_tokens=6000)
    r = extraire_json(rep, {"reponse": rep, "lancer": []})
    sortie = r.get("reponse") or rep
    for cle in r.get("lancer", []) or []:
        if cle in AGENTS:
            sortie += f"\n▶ {lancer(cle)}"
    return sortie


def lancer(cible: str) -> str:
    cles = list(AGENTS) if cible in ("tout", "tous", "all") else [cible]
    resultats = []
    for cle in cles:
        if cle not in AGENTS:
            resultats.append(f"Agent inconnu : {cle}")
            continue
        nom, fn = AGENTS[cle]
        try:
            resultats.append(f"{nom} : {fn()}")
        except Exception as e:  # un agent en panne ne bloque pas les autres
            resultats.append(f"{nom} : erreur — {e}")
            salon_poster(nom, f"⚠️ Erreur : {str(e)[:500]}", pour="Patron")
    return "\n".join(resultats)
