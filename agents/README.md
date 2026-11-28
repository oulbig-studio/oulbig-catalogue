# Équipe d'agents OULBIG

Des agents IA (Claude) qui travaillent pour tes marques et **discutent entre eux
et avec toi sur WhatsApp**.

| Agent | Rôle |
|---|---|
| 💬 Réponse-BD | Instagram + Facebook : quand quelqu'un commente « BD », « je veux »… il reçoit la BD **en message privé** + une petite réponse publique |
| 📸 Insta-Deals | Instagram : opportunités de vente, d'achat, de deals, de collaborations (hashtags + web) |
| 📸 Insta-Marketing | Instagram : prépare les posts des marques (titres du catalogue, tendances) |
| 📘 FB-Deals | Facebook : opportunités de vente, d'achat et de deals |
| 📘 FB-Marketing | Facebook : prépare les posts de la page |
| 💰 FB-Ads | Facebook/Instagram Ads : bilan 7 jours, propose pause / reprise / budget, idées de pubs |
| 🔭 Tendances | Sujets de livres demandés, applications à créer, autres sujets pour investir |
| 🎯 Coordinateur | Lit tes messages WhatsApp, anime les réunions, exécute ce que tu valides |

**Comment ils se parlent** : un salon commun (`agents/state/salon.json`), recopié sur
ton WhatsApp. Tendances donne ses sujets aux agents Marketing et Ads ; les Deals
signalent les meilleures pistes à FB-Ads ; Réponse-BD dit à Marketing quelles BD
plaisent. Chaque matin, une **réunion** : chaque agent réagit à ce que disent les
autres, puis le Coordinateur t'envoie une synthèse.

**Sécurité** : rien n'est publié et aucun euro n'est dépensé sans ton « OK n » sur
WhatsApp (sauf `"auto_publier": true` dans `config.json`). Le budget pub ne peut
jamais dépasser `budget_jour_max_centimes`.

## Sur WhatsApp
```
aide                 → la liste des commandes
OK 12 / NON 12       → valider / refuser la proposition 12
lance tendances      → (ou bd, insta-deals, insta-marketing, fb-deals, fb-marketing, fb-ads, tout)
réunion              → les agents discutent et te font une synthèse
attente              → propositions en attente
@tendances quels livres pour enfants marchent en ce moment ?
n'importe quel message → le Coordinateur répond ou fait travailler les agents
```

## Mise en route
1. **Remplis `agents/config.json`** : tes BD (`reponse_bd.bds` : titre + lien PDF/page),
   les mots-clés, les marques, les hashtags, le budget pub maximum.
2. **Meta (developers.facebook.com)** : une app « Business » reliée à ta Page Facebook
   et au compte Instagram professionnel, avec les produits *WhatsApp*, *Messenger*,
   *Instagram* et *Marketing API*. Permissions : `pages_manage_posts`,
   `pages_read_engagement`, `pages_manage_engagement`, `pages_messaging`,
   `instagram_basic`, `instagram_content_publish`, `instagram_manage_comments`,
   `instagram_manage_messages`, `ads_management`, `ads_read`.
   Crée un **jeton d'utilisateur système** (Business Manager → Utilisateurs système).
3. **Secrets GitHub** (Settings → Secrets and variables → Actions) :

   | Secret | Valeur |
   |---|---|
   | `ANTHROPIC_API_KEY` | clé API Claude (console.anthropic.com) |
   | `META_PAGE_TOKEN` | jeton de la Page (système) |
   | `META_PAGE_ID` | ID de la Page Facebook |
   | `META_IG_USER_ID` | ID du compte Instagram pro |
   | `META_AD_ACCOUNT_ID` | ID du compte publicitaire (sans `act_`) |
   | `META_ADS_TOKEN` | (option) jeton avec `ads_management`, sinon le jeton de Page |
   | `WHATSAPP_TOKEN` / `WHATSAPP_PHONE_ID` | WhatsApp Cloud API |
   | `WHATSAPP_PATRON` | ton numéro, format international sans + (ex. 2126XXXXXXXX) |

4. Le workflow **Agents OULBIG** tourne tout seul (BD toutes les 30 min, veille à 06:11 UTC,
   marketing + pubs + réunion à 08:41 UTC). Bouton « Run workflow » pour lancer à la main.

### Pour discuter en direct sur WhatsApp (recommandé)
GitHub Actions ne peut pas *recevoir* tes messages. Pour répondre « OK 3 » ou parler aux
agents, héberge le petit serveur (VPS Hostinger, Render, Railway…) :
```
pip install -r agents/requirements.txt
gunicorn -w 1 -b 0.0.0.0:8000 agents.server:app
```
mêmes variables que ci-dessus + `META_VERIFY_TOKEN` (mot de passe de ton choix) et
`META_APP_SECRET`. Dans l'app Meta, webhook `https://ton-domaine/webhook` abonné à
`messages` (WhatsApp), `comments` (Instagram) et `feed` (Page). Les BD partent alors
**en quelques secondes** au lieu de 30 min.

Sans serveur, tu peux valider une proposition depuis GitHub : Run workflow → `commande OK 3`.

### Bon à savoir
- WhatsApp : hors fenêtre de 24 h après ton dernier message, Meta n'accepte qu'un
  *modèle* approuvé. Crée-en un (ex. « rapport_agents ») et mets son nom dans
  `whatsapp.modele_relance` ; les rapports t'attendent et arrivent dès que tu réponds.
- Instagram/Facebook n'autorisent **qu'une réponse privée par commentaire**, dans les 7 jours.
- Les agents Deals ne contactent personne : ils te donnent le lien et un message d'approche.
- Coût : chaque agent fait un appel Claude (avec recherche web pour Tendances et Deals).
