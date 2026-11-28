"""Lancer les agents à la main ou depuis GitHub Actions.

  python -m agents.run tendances          # un agent
  python -m agents.run tout               # toute l'équipe
  python -m agents.run reunion            # les agents discutent + synthèse
  python -m agents.run commande "OK 3"    # comme un message WhatsApp
"""
import sys

from . import equipe


def main(argv: list[str]) -> int:
    if not argv:
        print(__doc__)
        print("Agents :", ", ".join(equipe.AGENTS))
        return 1
    cible = argv[0].lower()
    if cible in ("reunion", "réunion"):
        print(equipe.reunion())
    elif cible == "commande":
        print(equipe.commande(" ".join(argv[1:])))
    else:
        for c in argv:
            print(equipe.lancer(c.lower()))
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
