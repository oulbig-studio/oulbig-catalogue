# Tester l'épisode 1 sur Google Flow (Veo 3.1 Lite)

**Coût du test : gratuit.** Chaque compte reçoit 50 crédits par jour, et un plan Veo 3.1 Lite coûte 10 crédits : vous pouvez donc faire **5 plans par jour**.

## 1. Préparer (une seule fois)
1. Ouvrez **labs.google/flow** et connectez-vous avec votre compte Google.
2. Créez un projet nommé « L'Effet d'Amour – Ép. 1 ».
3. Dans les réglages du projet (l'icône de paramètres près du champ de prompt), choisissez :
   - modèle **Veo 3.1 Lite** ;
   - format **9:16 (portrait)** ;
   - **1 vidéo par prompt** (sinon chaque prompt coûte le double).
4. Gardez sous la main la planche de H : `references/H-reference.webp`.

## 2. Les 3 modes de Flow, et lequel utiliser

| Mode | Quand l'utiliser |
|---|---|
| **Texte → vidéo** | Plans sans personnage principal : car sur la route, ruelles, âne, fontaine |
| **Images → vidéo** (« Frames to Video ») | Vous donnez la **première image** du plan, et Veo l'anime. C'est le plus sûr pour garder le visage de H |
| **Ingrédients → vidéo** | Vous donnez la planche de H comme ingrédient, et Veo crée une scène avec lui. Pratique, mais le visage peut varier un peu |

**Méthode conseillée pour les plans avec H :**
1. Dans Flow, créez d'abord une image (bouton image, modèle d'image de Google) avec la planche de H en référence et la description du plan.
2. Utilisez cette image en **Images → vidéo**.

## 3. Le son
Votre voix off ElevenLabs sera posée au montage. Ajoutez donc à la fin de chaque prompt :
`Ambient sound only, no dialogue, no voice, no music.`
Veo génère alors les bruits (car, appel à la prière, ruelle) sans inventer de voix.

## 4. Les 5 plans à tester en premier (une journée gratuite)

Copiez-collez ces prompts : ils sont adaptés à Veo (8 s, verticaux, son d'ambiance).

**Test 1 – plan 3, le car (texte → vidéo)**
```
Vertical 9:16 cinematic shot, golden hour. A coach bus drives along a straight road across a vast ochre plain toward Marrakech; the dark jagged Atlas mountains on the horizon; the red city walls glowing in the low sun; dust floating in the light. Slow aerial tracking shot following the bus. 35mm film look, warm tones. Ambient sound only: engine hum, wind. No dialogue, no voice, no music.
```

**Test 2 – plan 7, la médina (ingrédients ou images → vidéo, avec la planche de H)**
```
Vertical 9:16. Narrow Marrakech medina alley at blue hour, wrought-iron lanterns casting warm golden light on ochre walls. A young man, 23, light-brown skin, very short black curly hair, light stubble, camel wool overcoat over a cream sweater, walks slowly toward camera pulling a rolling suitcase, looking around with emotion. An old man with prayer beads passes by. Handheld camera, shallow depth of field, 35mm film look. Ambient sound: distant call to prayer echoing between minarets, footsteps, suitcase wheels on stone. No dialogue, no music.
```

**Test 3 – plan 11, la fontaine à sec (images → vidéo avec H)**
```
Vertical 9:16. A small medina square at dusk, a dry old stone fountain full of dead leaves and dust. The same young man in a camel overcoat stands still in front of it, holding his suitcase, melancholic, remembering. Very slow push-in toward his face. Warm fading light, 35mm film look. Ambient sound: distant city murmur, wind moving dry leaves. No dialogue, no music.
```

**Test 4 – plan 12-13, le calligraphe (texte → vidéo)**
```
Vertical 9:16. Inside a tiny calligraphy shop in the Marrakech medina at night, an old Moroccan man, about 75, white beard, sits cross-legged on a worn burgundy velvet cushion, writing elegant Arabic calligraphy with a fine reed pen on thick paper; a small oil lamp makes shadows dance on his serene face. He slowly raises his eyes toward the camera with a calm, knowing look. Close-up, shallow depth of field, 35mm film look. Ambient sound: pen scratching paper, oil lamp crackle. No dialogue, no music.
```

**Test 5 – plan 16, la porte (images → vidéo avec H)**
```
Vertical 9:16. Night, a quiet sloping residential street in Marrakech, a street lamp flickering, white jasmine spilling over a wall. The same young man in a camel overcoat stops in front of a carved wooden family door, sets down his suitcase, hesitates one second, then raises his hand and knocks twice. Static camera, then slow push-in on his hand. 35mm film look. Ambient sound: crickets, two knocks on wood. No dialogue, no music.
```

## 5. Juger le test
- Le visage de H est-il le même dans les tests 2, 3 et 5 ? C'est le point le plus important.
- Le rendu « film » est-il assez beau en 720p sur un téléphone ?
- Téléchargez les plans réussis, montez-les dans CapCut avec les mp3 de `voix-off/ep01/`, et regardez l'enchaînement.

Si le test est concluant, prenez **Flow Pro** pour faire les 16 plans de l'épisode 1 (environ 50 générations avec les essais), puis la suite.
