"""Serveur webhooks : WhatsApp (tes messages aux agents) + commentaires
Instagram/Facebook (envoi immédiat des BD).

  gunicorn -w 1 -b 0.0.0.0:8000 agents.server:app

URL de webhook à donner à Meta : https://ton-domaine/webhook
"""
import hashlib
import hmac
import os
import threading

from flask import Flask, abort, request

from . import core, equipe

app = Flask(__name__)
_verrou = threading.Lock()  # une seule tâche à la fois sur les fichiers d'état


def _signature_ok() -> bool:
    secret = os.environ.get("META_APP_SECRET", "").encode()
    if not secret:
        return True  # à configurer en production
    recue = request.headers.get("X-Hub-Signature-256", "")
    attendue = "sha256=" + hmac.new(secret, request.get_data(), hashlib.sha256).hexdigest()
    return hmac.compare_digest(recue, attendue)


@app.get("/webhook")
def verifier():
    if (request.args.get("hub.mode") == "subscribe"
            and request.args.get("hub.verify_token") == os.environ.get("META_VERIFY_TOKEN")):
        return request.args.get("hub.challenge", "")
    abort(403)


@app.post("/webhook")
def recevoir():
    if not _signature_ok():
        abort(403)
    corps = request.get_json(silent=True) or {}
    # On répond tout de suite à Meta ; le travail se fait en arrière-plan.
    threading.Thread(target=_traiter, args=(corps,), daemon=True).start()
    return "ok"


def _traiter(corps: dict) -> None:
    with _verrou:
        try:
            objet = corps.get("object")
            for entree in corps.get("entry", []):
                for ch in entree.get("changes", []):
                    v = ch.get("value", {})
                    if objet == "whatsapp_business_account":
                        _whatsapp(v)
                    elif objet == "instagram" and ch.get("field") == "comments":
                        equipe.traiter_commentaire("instagram", v.get("id"), v.get("text", ""),
                                                   (v.get("from") or {}).get("username", ""))
                    elif objet == "page" and ch.get("field") == "feed" \
                            and v.get("item") == "comment" and v.get("verb") == "add" \
                            and (v.get("from") or {}).get("id") != os.environ.get("META_PAGE_ID"):
                        equipe.traiter_commentaire("facebook", v.get("comment_id"), v.get("message", ""),
                                                   (v.get("from") or {}).get("name", ""))
        except Exception as e:  # ne jamais faire tomber le serveur
            print("Erreur webhook :", e)


def _whatsapp(v: dict) -> None:
    patron = os.environ.get("WHATSAPP_PATRON", "")
    for m in v.get("messages", []):
        if m.get("from") != patron:
            continue  # seul le patron commande les agents
        if m.get("type") != "text":
            core.wa_envoyer("J'ai besoin d'un message texte 🙂 (tape « aide »)")
            continue
        texte = m["text"]["body"]
        try:
            reponse = equipe.commande(texte)
        except Exception as e:
            reponse = f"⚠️ Erreur : {e}"
        core.wa_envoyer(reponse)


@app.get("/")
def sante():
    return "Agents OULBIG en ligne ✅"
