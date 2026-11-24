# L'Effet d'Amour — prompts vidéo, épisodes 2 à 5

Les durées de plan sont calées sur les fichiers audio de `voix-off/epXX/`. Dans chaque tableau, la colonne **Audio** donne le fichier à poser sur le plan au montage, avec sa durée.

## Règles communes (à appliquer à chaque plan)

- **Format** : 9:16, 1080p (720p pour les tests), 24 i/s.
- **Durées** : Kling et Seedance génèrent des plans de 5 ou 10 s. On coupe au montage pour suivre l'audio.
- **Image de départ** : pour chaque plan où un personnage apparaît, utilisez l'image-to-video à partir de sa planche de référence (`references/`).
- **Modèles conseillés** :
  - plans d'émotion et gros plans de visage : Seedance 2.0 ou Veo 3.1 ;
  - plans d'ambiance : Kling 3.0, moins cher.
- **Synchronisation des lèvres** : les plans marqués 🎙️ montrent un personnage qui parle à l'écran. Générez d'abord le plan sans voix, puis passez-le dans l'outil **Lipsync de Higgsfield** avec le fichier mp3 indiqué. Tous les autres plans sont en voix off : pas de synchronisation à faire.
- **Suffixe de style à ajouter à la fin de chaque prompt** :
  `cinematic, 35mm film look, shallow depth of field, natural skin texture, subtle film grain, no text, no subtitles`
- **Lumière** :
  - présent (Marrakech, octobre) : chaude et dorée ;
  - flashbacks lycée : lumière de novembre pâle et oblique, couleurs légèrement désaturées ;
  - rêves : bleu nuit profond, halo doux.

## Personnages à préparer avant de lancer

| Réf. | Statut |
|---|---|
| H, 23 ans | ✅ `references/H-reference.webp` |
| H, 13-17 ans | à générer à partir de la planche de H : `same face as reference at 16, clean-shaven, slimmer, hoodie and school backpack` (le même prompt avec 13 ans pour l'épisode 3) |
| Béa, 23 ans | ✅ `references/Bea-reference.webp` |
| Béa, 17 ans (rêve, lycée) | à générer à partir de la planche de Béa (en cours) |
| Safaa, 17 ans | ✅ `references/Safaa-reference.jpg` (pour les épisodes 11 et suivants) |
| Mère, Père, Sœur, Frère | `Moroccan mother, 50, warm face, headscarf loosely tied, apron` · `Moroccan father, 55, grey stubble, calm, gardener's hands` · `Moroccan young woman, 19, artist, paint on her fingers` · `Moroccan teenage boy, 16, tall and lanky` |
| KALL, 23 et 17 ans | `Moroccan young man, relaxed charismatic smile, short fade haircut, stylish casual jacket, gold chain, always at ease` |
| Zed, 23 et 17 ans | `Moroccan young man, slim, quiet observant eyes, glasses, plain dark sweater, deadpan expression` |
| Minou | `small grey and white tabby kitten, irregular spots on forehead, curved question-mark tail, bright golden-yellow eyes` |

> Générez chaque personnage une fois en planche (face, profil, dos), comme celle de H, puis réutilisez toujours la même image.

---

## ÉPISODE 2 — La Chambre Bleue (≈ 85 s)

| # | Durée | Prompt vidéo | Audio |
|---|---|---|---|
| 1 | 5 s | `Dark bedroom at dawn, H (23) wakes up abruptly in a narrow single bed, sitting up breathing hard, soft grey morning light through the curtains, a painting of green trees on the wall in front of the bed` | ep02_01_H (6,4 s), début |
| 2 | 5 s | `Dream sequence: a bedroom with deep midnight-blue walls, a young woman (Béa, 17, dark curly hair loose, light freckles) sits on the floor with her back against the wall, knees folded, laughing softly, pale November afternoon light, dreamy soft glow, slow push-in` | ep02_01_H, fin + ep02_02_H (7,6 s) |
| 3 | 5 s | `Dream: close-up of Béa tilting her head as she smiles, blue light, hair moving slightly, the image slowly blurs and dissolves into white` | ep02_02_H, fin |
| 4 | 5 s | `H (23) lying back in bed staring at the ceiling, eyes wet, morning light, the green painting on the wall, silence of a sleeping house, static camera` | ep02_03_H (4,8 s) |
| 5 | 5 s | `H walks through a Moroccan family living room: new navy-blue armchairs, warm golden-beige walls, he sits in one armchair and touches the fabric, gentle morning light from the garden window` | — (ambiance, oiseaux) |
| 6 | 5 s | `Hallway with framed paintings: aerial view of Marrakech medina rooftops, a lake with reeds, and one painting of a woman seen from behind at the entrance of an alley at sunset; H stops in front of it, troubled, slow push-in on the painting` | — (musique) |
| 7 | 5 s | `Small lush Moroccan garden: mint, rosemary, basil, jasmine, geraniums in pots overflowing, a metal watering can against the wall, a peach tree with huge pink-orange peaches like lanterns; H picks a peach and bites into it, juice running on his fingers` | ep02_04_H (3,9 s) |
| 8 🎙️ | 5 s | `The father (55, grey stubble) in the garden, holding the watering can, looking up at the peach tree with a half smile, speaking calmly` | ep02_05_pere (2,3 s) |
| 9 | 5 s | `Moroccan breakfast ceremony in a warm kitchen: the mother folding msemen on a floured counter, the father pouring mint tea from high to make foam, the sister arranging honey, argan oil and olives, the teenage brother cutting fruit clumsily, H setting the table, everyone moving in quiet harmony` | — (musique, bruits de cuisine) |
| 10 🎙️ | 5 s | `At the breakfast table, the sister (19, paint on her fingers) looks straight at H and speaks sincerely, glasses of tea steaming between them` | ep02_06_soeur (3,2 s) |
| 11 🎙️ | 5 s | `H, moved, answers his sister softly across the table, throat tight` | ep02_07_H (2,4 s) |
| 12 🎙️ | 10 s | `The mother washing dishes at the sink, hands in warm water, eyes on the garden window, talking with a teasing smile; H beside her drying a plate, both laughing` | ep02_08_mere (7,1 s) → ep02_09_H (1,7 s) → ep02_10_mere (3,1 s) |
| 13 | 10 s | `Night, H alone in his bedroom sitting on the edge of the bed under the green painting, the room slowly tinted blue as if the dream is coming back, he looks toward the window, slow push-in to his face` | ep02_11_H (9,6 s) |
| 14 | 2 s | Noir, texte blanc : *À suivre…* | — |

---

## ÉPISODE 3 — Minou (≈ 80 s)

| # | Durée | Prompt vidéo | Audio |
|---|---|---|---|
| 1 🎙️ | 5 s | `Evening, Moroccan living room with navy-blue armchairs, the mother holding a glass of mint tea with both hands, eyes on the glass, speaking gently to H` | ep03_01_mere (1,6 s) |
| 2 | 5 s | `Flashback, cold bright November morning, golden low sun: a tiny grey and white tabby kitten with a question-mark tail appears in the frame of a Moroccan front door and walks in confidently like the owner, a 13-year-old boy (young H) watching amazed` | ep03_02_H (8,9 s), début |
| 3 | 5 s | `The kitten inspects the living room and the kitchen like a buyer visiting a house, then jumps onto young H's bed and settles there, golden eyes half closed` | ep03_02_H, fin + ep03_03_H (3,9 s) |
| 4 | 5 s | `Night, headlights from passing cars project moving shadows on the bedroom wall, the kitten leaps and chases the shadows with total concentration, then sits puzzled in front of the empty wall, young H laughing in bed` | — (musique douce) |
| 5 | 5 s | `Young H (15) studying late at a desk under a lamp, the grown cat sitting at the edge of the circle of light watching him, purring` | — (ronronnement) |
| 6 🎙️ | 5 s | `The cat scratches the front door, the mother opens it and the cat slips out into the medina alley; the mother smiles and says something calmly` | ep03_04_mere (1,6 s) |
| 7 | 5 s | `A small plate of food near the front door, untouched, the family glancing at the door during dinner, worried silence, evening light fading` | ep03_05_H (4,2 s) |
| 8 | 5 s | `The sister (younger, 15) on her knees looking under the sofa, she freezes; reveal: the cat hidden in the darkest narrow space under the sofa, barely breathing` | ep03_06_H (5,9 s) |
| 9 | 10 s | `Close-up, young H (15) holding the dying cat in his two hands, the cat suddenly very light, it looks at him one last time with veiled golden eyes, then slowly closes its eyes; tears in the boy's eyes, very soft light` | ep03_07_H (7,2 s) |
| 10 | 5 s | `Night, the father digging a small grave under a jasmine bush in the garden with an old spade, the mother with hands in her apron pockets, the sister crying silently, young H pressing the fresh earth with his palm` | ep03_08_H (5,7 s) |
| 11 | 5 s | `Present day, the same corner of the garden: the jasmine has grown huge, covering the wall, white flowers, H (23) standing in front of it at night` | ep03_09_H (2,2 s) |
| 12 | 10 s | `Slow dissolve: from the jasmine to a high-school classroom door in pale November light, the door slowly opens, a silhouette hesitates on the threshold (face not visible)` | ep03_10_H (10,2 s) |
| 13 | 2 s | Noir : *À suivre…* | — |

---

## ÉPISODE 4 — KALL, Zed et la nuit (≈ 90 s)

| # | Durée | Prompt vidéo | Audio |
|---|---|---|---|
| 1 🎙️ | 5 s | `H (23) in his bedroom in the afternoon, phone to his ear, smiling, relaxed after a family lunch` | ep04_01_H (2,6 s) |
| 2 🎙️ | 5 s | `KALL (23, relaxed charismatic smile) on a rooftop café terrace in Marrakech, phone to his ear, lighting up with joy` | ep04_02_KALL (2,8 s) |
| 3 🎙️ | 5 s | `H laughing on the phone, walking around his room` | ep04_03_H (3,5 s) |
| 4 🎙️ | 10 s | `KALL on the phone, gesturing with his free hand, excited, sunset light behind him` | ep04_04_KALL (6,9 s) + ep04_05_KALL (2,4 s) |
| 5 | 10 s | `Phone screen close-up: a text message conversation, then a reply appearing in 30 seconds "Je suis là. Comme si j'allais rater ça." / then H putting the phone away, smiling` | ep04_06_H (10,0 s) |
| 6 🎙️ | 5 s | `5:10 pm, KALL at H's front door, examining him from head to toe like an art expert dating a painting, then grinning` | ep04_07_KALL (5,5 s) |
| 7 🎙️ | 5 s | `Café in Guéliz, Zed (23, glasses, deadpan) sitting with a half-finished coffee, phone face down on the table, sees H and KALL arrive and says dryly` | ep04_08_Zed (3,4 s) |
| 8 🎙️ | 5 s | `H pulls a chair and sits, pointing at Zed with a smile; Zed raises an eyebrow and answers deadpan` | ep04_09_H (3,2 s) + ep04_10_Zed (2,8 s) |
| 9 | 5 s | `Montage: three friends crossing Marrakech at night, a rooftop terrace on the ramparts with the Atlas mountains turning blue then purple, a medina café owner winking at KALL, a hidden pizzeria in a Guéliz alley` | — (musique entraînante) |
| 10 | 5 s | `Late night rooftop terrace, Atlas as a dark line against a black sky, mint tea steaming, KALL and Zed laughing hard, H laughing too` | — (rires) |
| 11 | 10 s | `Close-up on H's face: the laughter fades, he looks down into his tea glass, a name rising in his throat, he raises the glass to his lips instead and says nothing; friends' laughter blurred in the background` | ep04_11_H (8,3 s) |
| 12 | 5 s | `Empty medina alley at night, chairs turned over on café tables, lanterns still glowing, H walking home alone, slow tracking shot from behind` | ep04_12_H (6,4 s) |
| 13 | 2 s | Noir : *À suivre…* | — |

---

## ÉPISODE 5 — Le Lac Thacker (≈ 85 s)

*Flashback : tous les personnages ont 17 ans.*

| # | Durée | Prompt vidéo | Audio |
|---|---|---|---|
| 1 | 10 s | `6 am, almost empty Moroccan bus station, pale cold light, buzzing neon, three 17-year-old boys with backpacks and thermos flasks (young H, young KALL, young Zed), excited and sleepy` | ep05_01_H (9,0 s) |
| 2 | 5 s | `Through a coach window: twisted olive trees, almond trees in white and pink blossom, intensely green fields after winter rain, mud-brick villages on the hills; young H with his forehead against the cold glass` | — (musique) |
| 3 🎙️ | 10 s | `Inside the coach, young Zed sitting next to young H, he puts his earphones back in his pocket and starts talking warmly; young KALL dozing two rows behind` | ep05_02_Zed (8,0 s) |
| 4 | 5 s | `A crowded 15-seat minibus with 25 people on a dirt road, a woman with a birdcage on her knees, an old man with a toothless kind smile, the driver honking joyfully at every turn, the three boys squeezed together laughing` | — (klaxons, oiseaux) |
| 5 | 5 s | `Reveal: around a red dirt track, a calm lake appears suddenly, milky blue-green water, hills covered with wild grass and yellow broom, reeds trembling, ducks swimming in a line, immense sky` | ep05_03_H (5,3 s) |
| 6 🎙️ | 5 s | `By the lake, young H unpacks his backpack piece by piece: a camping hammock, two inflatable mattresses, three fishing rods, a gas canister, a small stove; young KALL stares in disbelief and speaks` | ep05_04_KALL (2,4 s) |
| 7 🎙️ | 5 s | `Young H answers seriously while setting up the hammock between two trees, proud of his logic` | ep05_05_H (4,6 s) |
| 8 🎙️ | 5 s | `Young KALL holding up the gas canister, puzzled; young H explains calmly` | ep05_06_KALL (1,4 s) + ep05_07_H (4,2 s) |
| 9 🎙️ | 5 s | `Young Zed lets a one-second silence pass, then says with a completely flat face; the three boys burst into uncontrollable laughter, bent over, tears in their eyes` | ep05_08_Zed (2,1 s) + rires |
| 10 | 5 s | `Three boys fishing at the lake edge at noon, sunlight sparkling on the water, nothing biting, they talk and stay silent peacefully` | — |
| 11 🎙️ | 10 s | `Lunch on the grass: chicken cooking on a small stove, bread, olives, cheese; young KALL telling a story with big gestures, the two others crying with laughter` | ep05_09_KALL (7,3 s) |
| 12 | 5 s | `Young H lying in the hammock between two trees, smiling, watching his friends` | ep05_10_H (4,1 s) |
| 13 | 10 s | `Sunset over the lake: the water turns gold, then pink, then purple, then cold silver-grey; the three boys sitting shoulder to shoulder in silence; slow push-in on young H's face, lost in thought` | ep05_11_H (5,0 s) |
| 14 | 2 s | Noir : *À suivre…* | — |

---

## Budget estimé pour ces 4 épisodes

- 52 plans, pour environ 6 minutes de série.
- Avec environ 3 essais par plan, cela fait à peu près **1 800 à 2 400 crédits** sur Higgsfield :
  - Kling 3.0 pour les plans d'ambiance ;
  - Seedance pour les 12 plans d'émotion : épisode 2 plans 2-3 et 13, épisode 3 plans 9 et 12, épisode 4 plan 11, épisode 5 plan 13…
- C'est moins d'un mois d'abonnement Ultra (3 000 crédits).
