# OULBIG Catalogue & OULBIG Radio — v1.0

Catalogue automatique de la musique et des podcasts OULBIG (YouTube + Spotify),
mis à jour chaque jour par GitHub Actions, et OULBIG Radio qui le lit.

| Adresse | Contenu |
|---|---|
| https://oulbig-studio.github.io/oulbig-catalogue/ | OULBIG Radio (thème sombre, app installable) |
| https://oulbig-studio.github.io/oulbig-catalogue/jour/ | OULBIG Radio Jour (thème clair) |
| https://oulbig-studio.github.io/oulbig-catalogue/catalogue.json | Catalogue synchronisé |
| https://oulbig-studio.github.io/oulbig-catalogue/oulbig-widget.js | Widget pour tout autre site |

- `sync_catalogue.py` + `config.json` : synchronisation (YouTube @OULBIG, OULBIG – Topic, Spotify artiste).
- `.github/workflows/sync-catalogue.yml` : synchro quotidienne à 05:17 UTC, ou « Run workflow ».
- `wordpress/oulbig-catalogue/` : plugin WordPress (shortcodes `[oulbig_musique]`, `[oulbig_hitparade]`…).
- Procédure complète : `LISEZ-MOI-Procedure.pdf`.

Secrets à ajouter (Settings → Secrets and variables → Actions) : `YT_API_KEY`,
et en option `SPOTIFY_CLIENT_ID` / `SPOTIFY_CLIENT_SECRET`.
