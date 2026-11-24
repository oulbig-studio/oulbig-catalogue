# Super Family — EP08 « Lis pour grandir » : production vidéo

| Fichier | Contenu |
|---|---|
| `TEXTES_DE_MOUVEMENT_EP08_v2.txt` | Les 40 prompts image-to-video corrigés (qui parle dans chaque case, bouches fermées sinon, descriptions fidèles à l'album) |
| `dialogues_EP08.tsv` | Toutes les répliques de l'album, case par case : parole, pensée, narration ou bruitage, pour enregistrer les voix |

Les 40 cases (`EP08_case_X.Y.jpg`) et l'album PDF restent chez l'auteur : ils ne sont pas copiés dans ce dépôt.

## Bilan du test Hailuo (case 2.3)
- ✅ Style manga conservé, visage de Slaoui reconnaissable, très bel effet de lumière dorée, belle réaction d'émerveillement.
- ⚠️ La vidéo est en 16:9 (1344×768) alors que la case est en 4:3 (1456×1088) : le haut de la casquette et le bas du livre sont un peu recadrés. Pour une sortie verticale (TikTok, Reels), il faudra choisir entre un recadrage 9:16 au montage et un fond flou.
- ⚠️ Le filigrane « MINIMAX | Hailuo AI » en bas à droite indique un compte gratuit. Il disparaît avec un abonnement payant.
- ⚠️ La première image n'est pas exactement la case : le livre est fermé au départ, puis s'ouvre. C'est joli, mais pour enchaîner les cases proprement, utilisez la case comme **première image** (mode « Image to Video », pas « Subject reference »).
- ℹ️ Hailuo a généré une piste son. Coupez-la au montage si vous posez les voix ElevenLabs.

## Durée
40 cases × 5 s ≈ 3 min 20 par épisode. Avec environ 2,5 essais par case, il faut environ 100 générations par épisode.
