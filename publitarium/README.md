# Publitarium Events

Site mobile d'actualité culturelle, d'agenda, de festivals, de rendez-vous et de bonnes adresses au Maroc,
avec un résumé vocal de chaque article lu par Oulbig.

En ligne : https://publitarium.almoqayada.com

## Mettre à jour le contenu
Tout le contenu est dans `data/content.json` :
- `articles` : chaque article a un `voiceSummary` (texte lu par la synthèse vocale du navigateur).
  Pour utiliser la vraie voix d'Oulbig, déposez un MP3 (par ex. `audio/mon-article.mp3`) et ajoutez
  `"audioUrl": "audio/mon-article.mp3"` à l'article : le lecteur jouera ce fichier à la place.
- `events` : festivals, agenda, rendez-vous (`slots` = créneaux proposés dans le formulaire de réservation).
- `places` : bonnes adresses.

Les dates des festivals 2027 sont des périodes habituelles : à confirmer quand les organisateurs les annoncent.

## Demandes reçues
Les rendez-vous, inscriptions newsletter et messages sont enregistrés côté serveur dans `storage/`
(un fichier `.jsonl` par type et par mois, inaccessible depuis le web). Consultez-les via le gestionnaire
de fichiers Hostinger.
