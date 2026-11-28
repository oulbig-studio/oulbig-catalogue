# Cahiers numériques OULBIG

Deux produits numériques imprimables (A4), en 6 langues : français, anglais, espagnol, arabe classique, darija marocaine, allemand.

| Produit | Pages | Fichiers |
|---|---|---|
| **One Habit a Day**: 30 jours, une habitude par jour, bilans hebdomadaires, suivi de 30 jours, certificat | 23 | `pdf/one-habit-a-day-<langue>.pdf` |
| **Old Remedies Handbook**: avertissement santé, placard essentiel, 12 remèdes avec pages de notes, remèdes de famille, journal | 20 | `pdf/old-remedies-handbook-<langue>.pdf` |

Langues : `fr`, `en`, `es`, `ar` (arabe classique), `darija`, `de`.

## Modifier et régénérer
- Textes : `src/content/<langue>.js`
- Mise en page : `src/build.js`
- Régénérer : `node workbooks/src/build.js` (Playwright + Chromium nécessaires ; `LANGS=fr,en` pour ne générer que certaines langues)

Couvertures (PNG, portrait A4 1654×2339 et carré 2000×2000) : `covers/`, régénérer avec `node workbooks/src/covers.js`.

Couvertures Canva (tendance, 1587×2245) : `covers-canva/`, modifiables dans le dossier Canva « OULBIG — Couvertures cahiers ».

Polices : Playfair Display, Lora, Amiri, Noto Naskh Arabic (licence SIL OFL).
