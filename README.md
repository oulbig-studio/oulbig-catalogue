# OULBIG Radio — thème WordPress v2.0

Web radio OULBIG en 5 stations (FR, EN, ES, فصحى, دارجة), avec comptes auditeurs.

## Installation
1. Apparence → Thèmes → Ajouter → Téléverser un thème → `OULBIG-Radio-WordPress-v2.0.zip` → Activer.
   (Si la v1 est installée : « Remplacer l'actuel par la version téléversée ». Le thème « Jour » v1 n'est plus utile : choisis « Clair » dans les réglages.)
2. À l'activation, le thème crée ses tables, le rôle « Artiste OULBIG Radio » et ouvre les inscriptions (rôle Abonné).
3. Réglages → Permaliens → Enregistrer (une fois, pour l'API).
4. Menu **OULBIG Radio → Réglages** : apparence, Hit Parade, jingles, pubs vidéo, partenaires, artistes, réseaux sociaux.

## Ce que font les auditeurs
- Créer un compte (page d'inscription WordPress), voter (♥), commenter, créer des playlists privées ou publiques.
- Signaler la langue d'une piste (drapeau, ou en commentant) : après N signalements identiques (3 par défaut), la piste change de station. Les tiens s'appliquent tout de suite.
- Demander l'accès à la publication (section « Publier »).

## Ce que tu fais dans l'administration (menu OULBIG Radio)
- **Langues signalées** : appliquer ou annuler une correction de langue.
- **Commentaires** : publier (si la validation est activée) ou supprimer.
- **Demandes de publication** : accorder l'accès (rôle Artiste) ou refuser.
- **Titres proposés** : vérifier la vidéo, la langue, puis « Publier » pour la mettre dans la grille.

## Jingles et pubs vidéo
Colle le lien YouTube quand la vidéo est en ligne. Jingles : début de boucle et avant le Hit Parade. Pubs : toutes les N pistes. En attendant les vidéos, une carte partenaire s'affiche sur le lecteur.

## API (pour information)
`/wp-json/oulbig-radio/v1/` : bootstrap, like, comments, report, playlists, playlists/public, access, submit.
