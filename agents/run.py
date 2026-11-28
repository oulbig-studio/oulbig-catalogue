"""Lancer les agents à la main ou depuis GitHub Actions.

  python -m agents.run tendances          # un agent
  python -m agents.run tout               # toute l'équipe
  python -m agents.run reunion            # les agents discutent + synthèse
  python -m agents.run commande "OK 3"    # comme un message WhatsApp
  python -m agents.run whatsapp           # message relayé (variable WA_TEXTE), réponse sur WhatsApp
  python -m agents.run commentaire        # commentaire relayé (COM_PLATEFORME, COM_ID, COM_TEXTE, COM_AUTEUR)
"""
import os
import sys

from . import core, equipe


def main(argv: list[str]) -> int:
    if not argv:
        print(__doc__)
        print("Agents :", ", ".join(equipe.AGENTS))
        return 1
    cible = argv[0].lower()
    if cible in ("reunion", "réunion"):
        print(equipe.reunion())
    elif cible == "whatsapp":
        texte = os.environ.get("WA_TEXTE", "").strip()
        if texte:
            try:
                reponse = equipe.commande(texte)
            except Exception as e:  # le patron doit toujours avoir une réponse
                reponse = f"⚠️ Erreur : {e}"
            print(reponse)
            core.wa_envoyer(reponse)
    elif cible == "commentaire":
        e = os.environ
        envoye = equipe.traiter_commentaire(e.get("COM_PLATEFORME", ""), e.get("COM_ID", ""),
                                            e.get("COM_TEXTE", ""), e.get("COM_AUTEUR", ""))
        print("BD envoyée" if envoye else "Rien à envoyer")
    elif cible == "commande":
        print(equipe.commande(" ".join(argv[1:])))
    else:
        for c in argv:
            print(equipe.lancer(c.lower()))
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
