# Publitarium Events

Site mobile d'actualité culturelle, d'agenda, de festivals, de rendez-vous et de bonnes adresses au Maroc,
avec un résumé vocal de chaque article lu par Oulbig.

En ligne : https://publitarium.almoqayada.com

## Back-office
https://publitarium.almoqayada.com/admin/

- À la première visite, la page demande de **créer le mot de passe administrateur** : faites-le
  juste après la mise en ligne (tant qu'il n'existe pas, le premier visiteur pourrait le créer).
- Gestion des événements, festivals et rendez-vous, des articles (avec envoi du MP3 d'Oulbig),
  des bonnes adresses et des photos.
- Consultation et export Excel (CSV) des rendez-vous, inscriptions newsletter et messages.
- Couleurs du site (6 palettes prêtes ou couleurs libres), couleurs des rubriques, liste des villes.
- Mot de passe oublié : supprimez `storage/admin.json` avec le gestionnaire de fichiers Hostinger,
  puis recréez-le depuis `/admin/`.

Le contenu modifié est enregistré dans `storage/content.json` et les fichiers dans `uploads/` :
un nouveau déploiement Git ne les écrase pas.

## Contenu de départ
Le contenu livré avec le site est dans `data/content.json` (utilisé tant que rien n'a été modifié
dans le back-office) :
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

## Déploiement automatique
Chaque push sur `main` (ou sur la branche de travail) qui touche `publitarium/` ou
`wordpress-theme/publitarium-wp/` lance le workflow GitHub Actions « Déploiement Publitarium » :
il met à jour les branches `publitarium-site` / `publitarium-wp-theme` puis appelle les webhooks
Hostinger (secrets `HOSTINGER_HOOK_SITE` et `HOSTINGER_HOOK_WP`). Lancement manuel possible :
onglet Actions > Déploiement Publitarium > Run workflow.
