# Super Family EP08 — test sur Google Flow

Pour comparer avec le test Hailuo, on prend la même case (2.3), puis une case dialoguée (2.1).

## Réglages Flow
- Mode : **Images → vidéo** (« Frames to Video »). Importez la case comme **première image** (Start frame).
- Format : **16:9 (paysage)**. Les cases sont en 4:3, c'est le format le plus proche.
- Modèle : Veo 3.1 Lite. Si ce mode ne propose pas Lite, prenez Veo 3.1 Fast.
- 1 vidéo par prompt · 8 s.

## Test 1 — case 2.3 (`EP08_case_2.3.jpg`) : le livre magique
```
Animate this manga panel in the exact same 2D anime/manga illustration style, keeping the character's face, outfit, colours and line art identical; do not turn it into a realistic photo. Super Slaoui (the sailor captain with a white cap, navy jacket, white shirt and red sash) sits at a wooden table and slowly opens an old leather book; a warm burst of golden light and floating golden particles rises from the pages and lights up his amazed face, his eyes widening. Gentle natural motion: hair and clothes moving slightly, blinking, the oil lantern flickering, the sea shimmering through the arch behind him. Camera: slow push-in with a slight swirl. Mouth stays closed, no talking. No text, no speech bubbles, no subtitles. Ambient sound only: magical shimmer, soft sea waves.
```

## Test 2 — case 2.1 (`EP08_case_2.1.jpg`) : dialogue dans la ruelle — version corrigée
**Pourquoi le premier essai a échoué** : le prompt demandait des personnages qui parlent, puis « No voices ». Veo n'a pas su générer ce son contradictoire. Les voix seront de toute façon posées avec ElevenLabs.
**Réglage** : dans les paramètres de Flow, **désactivez le son** (génération sans audio). Ça réduit aussi les échecs.
```
Animate this manga panel in the exact same 2D anime/manga illustration style, keeping both characters' faces, outfits, colours and line art identical; do not turn it into a realistic photo. In a sunny Salé medina alley, Super Slaoui (the sailor captain with a white cap, navy jacket, white trousers and red sash) shrugs with open hands while his lips move as if speaking, then Super L3arbi (the headmaster in an olive-green three-piece suit holding a leather book) smiles, his lips moving briefly, and beckons him to follow down the alley. Gentle natural motion: hair and clothes moving slightly, blinking, soft sunlight. Camera: slow, steady push-in, almost static. No text, no speech bubbles, no subtitles.
```

## À comparer avec Hailuo
1. Le **style manga** est-il gardé, ou Veo rend-il l'image trop « réaliste » ?
2. Le **visage de Slaoui** reste-t-il identique du début à la fin ?
3. La vidéo **commence-t-elle exactement sur la case** ? (Chez Hailuo, le livre était fermé au départ.)
4. Y a-t-il un **filigrane** ?

Envoyez-moi les deux vidéos (et votre test Hailuo de la case 2.1, si vous l'avez) : je les compare image par image.
