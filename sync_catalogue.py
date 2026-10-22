#!/usr/bin/env python3
"""
OULBIG — Synchronisation du catalogue musique + podcasts
=========================================================
Récupère tout ce qui est publié sur YouTube (et Spotify / flux RSS podcast si configurés),
classe par langue (fr, en, es, ar = Fusha, ma = Darija), calcule le Hit Parade
et écrit un seul fichier : catalogue.json

Aucune dépendance : Python 3.9+ standard.

Variables d'environnement :
  YT_API_KEY             clé API YouTube Data v3 (recommandé)
  SPOTIFY_CLIENT_ID      optionnel
  SPOTIFY_CLIENT_SECRET  optionnel

Usage :
  python sync_catalogue.py                 # synchro réelle
  python sync_catalogue.py --demo          # génère un catalogue d'exemple (test des sites)
  python sync_catalogue.py --config autre.json --out public/catalogue.json
"""
import argparse, base64, json, os, re, sys, time, unicodedata
import urllib.parse, urllib.request, urllib.error
import xml.etree.ElementTree as ET
from datetime import datetime, timezone

LANGS = ["fr", "en", "es", "ar", "ma"]
TAGS = {  # tags reconnus dans les titres : [FR] (FR) #FR …
    "fr": ["fr", "français", "francais"],
    "en": ["en", "english", "eng"],
    "es": ["es", "español", "espanol", "esp"],
    "ar": ["ar", "fusha", "فصحى", "msa"],
    "ma": ["ma", "darija", "دارجة", "الدارجة", "dz"],
}
PODCAST_RE = re.compile(r"\b(ep\.?\s?\d+|épisode|episode|episodio|podcast)\b|الحلقة|حلقة|بودكاست", re.I)
NOISE_RE = re.compile(r"[\(\[\{][^\)\]\}]*(official|officiel|oficial|clip|video|vidéo|lyrics?|paroles|audio|visualizer|hd|4k|رسمي|كليب)[^\)\]\}]*[\)\]\}]", re.I)


# ---------------------------------------------------------------- utilitaires
def log(*a):
    print("[catalogue]", *a, file=sys.stderr)


def http_json(url, headers=None, data=None, retries=3):
    for i in range(retries):
        try:
            req = urllib.request.Request(url, headers=headers or {}, data=data)
            with urllib.request.urlopen(req, timeout=30) as r:
                return json.loads(r.read().decode("utf-8"))
        except urllib.error.HTTPError as e:
            body = e.read().decode("utf-8", "ignore")[:300]
            if e.code in (429, 500, 502, 503) and i < retries - 1:
                time.sleep(2 * (i + 1)); continue
            raise RuntimeError(f"HTTP {e.code} sur {url.split('?')[0]} : {body}")
        except urllib.error.URLError as e:
            if i < retries - 1:
                time.sleep(2 * (i + 1)); continue
            raise RuntimeError(f"Réseau indisponible pour {url.split('?')[0]} : {e.reason}")


def http_text(url):
    req = urllib.request.Request(url, headers={"User-Agent": "OULBIG-catalogue/1.0"})
    with urllib.request.urlopen(req, timeout=30) as r:
        return r.read().decode("utf-8", "ignore")


def iso_duration(s):
    """PT3M32S -> 212"""
    m = re.match(r"P(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?", s or "")
    if not m:
        return 0
    d, h, mi, se = (int(x or 0) for x in m.groups())
    return d * 86400 + h * 3600 + mi * 60 + se


def hms_duration(s):
    """'1:02:03' / '9:21' / '561' -> secondes"""
    if not s:
        return 0
    parts = [int(p) for p in str(s).strip().split(":") if p.isdigit()]
    t = 0
    for p in parts:
        t = t * 60 + p
    return t


ARTIST_NAMES = ["OULBIG"]  # complété au démarrage avec les artistes de config.json


def clean_title(t, artist=None):
    t = NOISE_RE.sub("", t)
    for name in ([artist] if artist else []) + [n for n in ARTIST_NAMES if n != artist]:
        t = _strip_artist(t, name)
    return re.sub(r"\s{2,}", " ", t).strip(" -–—|·")


def _strip_artist(t, artist):
    t = re.sub(r"\.(mp3|wav|m4a)$", "", t.strip(), flags=re.I)
    t = re.sub(r"^\s*\d{2,3}\s+", "", t)                                  # « 002 HALA MAGHRIB »
    t = re.sub(rf"\s+(r\s+)?by\s+{re.escape(artist)}\s*$", "", t, flags=re.I)  # « … By OULBIG »
    t = re.sub(r"[\[\(#]\s*(fr|en|es|ar|ma|dz|fusha|darija|français|english|español)\s*[\]\)]?", "", t, flags=re.I)
    t = re.sub(rf"^\s*{re.escape(artist)}\s*[-–—|:·]\s*", "", t, flags=re.I)
    t = re.sub(rf"\s*[-–—|·]\s*{re.escape(artist)}\s*$", "", t, flags=re.I)
    t = re.sub(rf"\s+(feat\.?|ft\.?|x)\s+{re.escape(artist)}\s*$", "", t, flags=re.I)
    return t


def norm_key(t):
    t = unicodedata.normalize("NFKD", clean_title(t).lower())
    t = "".join(c for c in t if not unicodedata.combining(c))
    return re.sub(r"[^\w؀-ۿ]+", "", t)


# --- Détection automatique de la langue (titres sans tag ni playlist) ---
LANG_WORDS = {
    "es": set("y yo tú eres soy estoy estás muy pero porque cuando quiero tengo tienes hay está como esta este eso nada todo nunca siempre corazón contigo amor mi el los las del mi mis tu tus te y o pero que qué cómo como cuando donde dónde sin con por para una uno es ser soy eres estoy está amor corazón vida mundo loco loca locura quiero todo nada mejor poco futuro cura noche día mujer contigo conmigo siempre nunca solo sola volvió ojos mirándote belleza voluntad rebelión hechizo plazo equilibrio esconde verdad tiempo alma fuego luna sueño bailando entre dos mundos chispa sé no más ha crecido salir zona confort receta felicidad reglas conversación pequeños hábitos grandes cambios momentos cambian conviértete persona nueva millonario empieza hoy ahora lee crecer vive momento deja perder tiempo consejos vivir manera correcta energía poder querer seis días".split()),
    "en": set("is are was were a an it this that what when where who can can't don't won't i'll we they he she his her with from just like know want need got gonna wanna baby oh yeah the of you your i i'd i'm it's my me love girl boy day night light fight nothing else dream be man to see hoping breaks slow builders civilization have said all and in with our fate mad at without never ever always heart country's daughter forever baby tonight life world time feel like just know want need rules better tiny big changes become new person recipe happiness greatest school start today now read grow stop wasting stepping out comfort moments change everything ten forty habits people truly interesting power will energy six days".split()),
    "fr": set("et est un une dans pour avec mon ma mes ton ta tes son sa ses nous vous ils elles on c'est qu'il suis sommes êtes sont mais donc car quand comme aussi plus très le les la des du de je j'ai j'espérais te toi moi tout tous va bien ce soir ne pas que qui sans d'amour amour désir rêve rêver elle lui cesse essayé notre nos votre sort jours vie monde coeur cœur jamais toujours encore nuit seconde chance laisse dire voir tant être juste charme trompeur soupir là où ô tient debout avenir seul mot masque rien".split()),
}
DARIJA_LATIN = set("liam fhad denia dnya lmouja nhar chfti fia bghit rani dyal diali dialk zwin zwina bzaf wach kifach lbare7 ghir mabghitch 3lach 3ini hbal mama khoya bladi".split())
DARIJA_AR = set("ديال ديالي ديالك بغيت بغيتي كاين كاينش بزاف شكون علاش واش هاد هادي دابا ماشي ديما نتي نتا حنا ضو شفت طايح تبدلات زاد هبالي هْبالي كلشي بزي ليام لِيَّام نبغيك كنبغيك بلادي راه يبغيك نشوفك كنتمنى عطاك حياتو زينك هبلني الزين نهار شفتي فيا عقلتي مشينا نصاصات عينيا گولي فين غادي عارفش طريق طويلة جوال".split())
NAME_HINTS = [  # mots explicites dans le titre : « Fr », « Darija », « English », « Spanish »…
    ("ma", r"\b(darija|dz)\b|دارجة"),
    ("es", r"\b(spanish|español|espanol|esp)\b"),
    ("en", r"\b(english|eng)\b"),
    ("fr", r"\b(fr|français|francais|french)\b"),
    ("ar", r"\b(fusha|msa)\b|فصحى"),
    ("ma", r"\bchaabi\b"),
]


def detect_lang(title, fallback):
    low = title.lower()
    for lang, words in TAGS.items():
        for w in words:
            if re.search(rf"[\[\(#]\s*{re.escape(w)}\s*[\]\)]?(\s|$)", low):
                return lang
    for lang, rx in NAME_HINTS:
        if re.search(rx, low):
            return lang
    if re.search(r"[\u0600-\u06FF]", title):
        toks = re.findall(r"[\u0600-\u06FF\u064B-\u0652]+", title)
        plain = [re.sub(r"[\u064B-\u0652]", "", t) for t in toks]
        if any(t in DARIJA_AR or re.sub(r"[\u064B-\u0652]", "", t) in DARIJA_AR for t in toks):
            return "ma"
        # transcription darija : consonne initiale avec soukoun (طْريق, نْهار, مْشينا)
        if any(len(t) > 2 and t[1] == "\u0652" for t in toks):
            return "ma"
        # négation darija : « ما ... ش » (ماندمتيش, ما ترجعيش, ما بغيتش)
        if any(t.startswith("ما") and t.endswith("ش") and len(t) > 3 for t in plain) or \
           ("ما" in plain and any(t.endswith("ش") and len(t) > 2 for t in plain)):
            return "ma"
        return "ar"
    words = re.findall(r"[a-zà-ÿñ']+", low)
    if any(w in DARIJA_LATIN for w in words):
        return "ma"
    score = {k: 0 for k in LANG_WORDS}
    for w in words:
        for k, vocab in LANG_WORDS.items():
            if w in vocab:
                score[k] += 1
    if re.search(r"[ñáíóú¿¡]", low):
        score["es"] += 2
    if re.search(r"[èêëàâçùûœ]", low):
        score["fr"] += 2
    best = max(score, key=score.get)
    if score[best] == 0:
        return fallback
    ranked = sorted(score.values(), reverse=True)
    return best if ranked[0] > ranked[1] else fallback


def _clean_desc(desc):
    """Garde les lignes utiles d'une description YouTube (paroles, présentation)."""
    out = []
    for line in (desc or "").splitlines():
        low = line.lower()
        if re.search(r"https?://|www\.|@|#|spotify|instagram|tiktok|facebook|subscribe|abonne|suscr|اشترك|copyright|©|℗|ditto|distrokid|provided to youtube|auto-generated", low):
            continue
        out.append(line)
    return " ".join(out)[:1500]


def _arabic_variant(text):
    """Arabe standard (ar) ou darija (ma) d'après les marqueurs darija du texte."""
    toks = re.findall(r"[\u0600-\u06FF\u064B-\u0652]+", text)
    plain = [re.sub(r"[\u064B-\u0652]", "", t) for t in toks]
    hits = sum(1 for t in plain if t in DARIJA_AR)
    hits += sum(1 for t in toks if len(t) > 2 and t[1] == "\u0652")
    hits += sum(1 for t in plain if t.startswith("ما") and t.endswith("ش") and len(t) > 3)
    hits += sum(1 for t in plain if re.match(r"^(كن|كي|كت|كا)[\u0600-\u06FF]{2,}", t))  # préfixe verbal darija
    return "ma" if hits >= (1 if len(plain) < 12 else 2) else "ar"


def _latin_scores(text):
    low = text.lower()
    words = re.findall(r"[a-zà-ÿñ']+", low)
    score = {k: 0.0 for k in LANG_WORDS}
    for w in words:
        for k, vocab in LANG_WORDS.items():
            if w in vocab:
                score[k] += 1
    score["es"] += 2 * len(re.findall(r"[ñ¿¡]", low)) + 0.5 * len(re.findall(r"[áíóú]", low))
    score["fr"] += 0.5 * len(re.findall(r"[èêëàâçùûœî]", low))
    darija = sum(1 for w in words if w in DARIJA_LATIN)
    return score, darija


def _decide(score, min_top=1, ratio=1.0):
    best = max(score, key=score.get)
    ranked = sorted(score.values(), reverse=True)
    if ranked[0] >= min_top and ranked[0] > ranked[1] * ratio:
        return best
    return None


AUDIO_MAP = {"fr": "fr", "en": "en", "es": "es", "ar": "ar"}


def detect_lang_full(title, desc="", audio="", fallback="fr"):
    """Langue d'une vidéo : (langue, source). Ordre : tag du titre, indices du titre,
    écriture/vocabulaire du titre, paroles de la description, langue audio YouTube."""
    low = title.lower()
    for lang, words in TAGS.items():
        for w in words:
            if re.search(rf"[\[\(#]\s*{re.escape(w)}\s*[\]\)]?(\s|$)", low):
                return lang, "tag"
    for lang, rx in NAME_HINTS:
        if re.search(rx, low):
            return lang, "titre"
    body = _clean_desc(desc)
    if re.search(r"[\u0600-\u06FF]", title):
        return _arabic_variant(title + " " + body), "titre"
    sc, darija = _latin_scores(title)
    if darija:
        return "ma", "titre"
    got = _decide(sc, min_top=1, ratio=1.0)
    if got and sc[got] >= 2:
        return got, "titre"
    if body:
        ar_chars = len(re.findall(r"[\u0600-\u06FF]", body))
        lat_chars = len(re.findall(r"[A-Za-zÀ-ÿ]", body))
        if ar_chars > 20 and ar_chars > lat_chars:
            return _arabic_variant(body), "description"
        dsc, ddar = _latin_scores(body)
        if ddar >= 3:
            return "ma", "description"
        dgot = _decide(dsc, min_top=3, ratio=1.4)
        if dgot:
            return dgot, "description"
    if got:
        return got, "titre"
    a = (audio or "").lower().split("-")[0]
    if a in AUDIO_MAP:
        lang = AUDIO_MAP[a]
        if lang == "ar":
            lang = _arabic_variant(title + " " + body)
        return lang, "audio"
    return fallback, "défaut"


# Épisodes parlés (développement personnel) reconnus à leur thème, dans les 5 langues
TALK_RE = re.compile("|".join([
    r"habit", r"habitude", r"hábito", r"habito", r"عادات", r"عادة",
    r"millionaire", r"millionnaire", r"millonario", r"مليونير",
    r"change (your|tout)", r"changer (de vie|tout)", r"cambia tu vida", r"غيّ?ر حياتك", r"غَيِّرْ حَيَاتَكَ",
    r"(live|vis|vive) (the|le|el) moment", r"عِ?ش اللحظة", r"moments? (that|qui|que)", r"momentos que", r"اللحظات التي",
    r"read to grow", r"lis pour grandir", r"lee para crecer", r"اقرأ لتنمو",
    r"start your life", r"commence ta vie", r"empieza tu vida", r"ابدأ حياتك",
    r"new person", r"nouvelle personne", r"persona nueva", r"إنساناً جديداً", r"انسانا جديدا",
    r"wasting your time", r"gaspiller ton temps", r"perder tu tiempo", r"لا تُ?ضِ?ع وقتك",
    r"recipe for happiness", r"recette du bonheur", r"receta de la felicidad", r"وصفة السعادة",
    r"comfort zone", r"zone de confort", r"zona de confort", r"منطقة الراحة",
    r"conversation", r"conversación", r"محادثة", r"consejos", r"conseils", r"interesting people",
    r"gens intéressants", r"personas interesantes", r"أعظم مدرسة", r"greatest school", r"plus grande école",
    r"leadership", r"liderazgo", r"القيادة",
]), re.I)


def detect_type(title, fallback="song", duration=0):
    if PODCAST_RE.search(title) or TALK_RE.search(title):
        return "podcast"
    if duration and duration >= 430:  # aucune chanson ne dépasse ~7 min
        return "podcast"
    return fallback


# ---------------------------------------------------------------- YouTube
def yt_playlist_video_ids(pid, key):
    ids, token = [], ""
    while True:
        q = urllib.parse.urlencode({"part": "contentDetails", "playlistId": pid, "maxResults": 50, "key": key, "pageToken": token})
        data = http_json("https://www.googleapis.com/youtube/v3/playlistItems?" + q)
        ids += [it["contentDetails"]["videoId"] for it in data.get("items", [])]
        token = data.get("nextPageToken")
        if not token:
            return ids


def yt_videos(ids, key):
    out = {}
    for i in range(0, len(ids), 50):
        q = urllib.parse.urlencode({"part": "snippet,contentDetails,statistics,status", "id": ",".join(ids[i:i + 50]), "key": key})
        # snippet : titre, description (paroles), chaîne, langue audio déclarée
        for v in http_json("https://www.googleapis.com/youtube/v3/videos?" + q).get("items", []):
            if v.get("status", {}).get("privacyStatus") != "public":
                continue
            sn = v["snippet"]
            th = sn.get("thumbnails", {})
            out[v["id"]] = {
                "id": v["id"],
                "title": sn["title"],
                "published": sn.get("publishedAt", ""),
                "duration": iso_duration(v["contentDetails"].get("duration")),
                "views": int(v.get("statistics", {}).get("viewCount", 0)),
                "thumb": (th.get("high") or th.get("medium") or th.get("default") or {}).get("url", ""),
                "embeddable": v.get("status", {}).get("embeddable", True),
                "channel": sn.get("channelId", ""),
                "channel_title": sn.get("channelTitle", ""),
                "desc": sn.get("description", ""),
                "audio": sn.get("defaultAudioLanguage") or sn.get("defaultLanguage") or "",
            }
    return out


def yt_resolve_handle(handle, key):
    q = urllib.parse.urlencode({"part": "id", "forHandle": handle.lstrip("@"), "key": key})
    items = http_json("https://www.googleapis.com/youtube/v3/channels?" + q).get("items", [])
    return items[0]["id"] if items else None


def artist_table(cfg):
    """{nom: {youtube:[...], handles:[...], spotify:[...], links:{...}}} — compatible avec l'ancien format."""
    arts = dict(cfg.get("artists") or {})
    if not arts:
        arts[cfg.get("artist", "OULBIG")] = {
            "youtube": cfg.get("youtube", {}).get("channel_ids", []),
            "spotify": cfg.get("spotify", {}).get("artist_ids", []),
            "links": cfg.get("links", {}),
        }
    return arts


def yt_rss(channel_id):
    """Sans clé : 15 dernières vidéos seulement, sans durée."""
    xml = http_text(f"https://www.youtube.com/feeds/videos.xml?channel_id={channel_id}")
    ns = {"a": "http://www.w3.org/2005/Atom", "yt": "http://www.youtube.com/xml/schemas/2015", "media": "http://search.yahoo.com/mrss/"}
    root, out = ET.fromstring(xml), {}
    for e in root.findall("a:entry", ns):
        vid = e.find("yt:videoId", ns).text
        stats = e.find("media:group/media:community/media:statistics", ns)
        out[vid] = {"id": vid, "title": e.find("a:title", ns).text, "published": e.find("a:published", ns).text,
                    "duration": 0, "views": int(stats.get("views", 0)) if stats is not None else 0,
                    "thumb": f"https://i.ytimg.com/vi/{vid}/hqdefault.jpg", "embeddable": True}
    return out


def collect_youtube(cfg):
    yc = cfg.get("youtube", {})
    key = os.environ.get("YT_API_KEY", "").strip()
    default_lang, items = cfg.get("default_lang", "fr"), {}
    assigned = {}  # videoId -> (lang, type) depuis les playlists configurées

    arts = artist_table(cfg)
    chan_artist = {}
    for name, a in arts.items():
        for ch in a.get("youtube", []):
            chan_artist[ch] = name
        for h in a.get("handles", []):
            if key:
                try:
                    cid = yt_resolve_handle(h, key)
                    if cid:
                        chan_artist[cid] = name
                        log(f"Chaîne {h} → {cid} ({name})")
                except RuntimeError as e:
                    log("handle ignoré", h, "→", e)
    channels = list(dict.fromkeys(list(chan_artist) + yc.get("channel_ids", [])))

    if not key:
        log("YT_API_KEY absente : mode flux RSS (15 dernières vidéos, durées estimées).")
        for ch in channels:
            try:
                items.update(yt_rss(ch))
            except Exception as e:
                log("flux RSS de la chaîne ignoré", ch, "→", e)
    else:
        for pl in yc.get("playlists", []):
            try:
                for vid in yt_playlist_video_ids(pl["id"], key):
                    assigned[vid] = (pl.get("lang"), pl.get("type"), pl.get("artist"))
            except RuntimeError as e:
                log("playlist ignorée", pl.get("id"), "→", e)
        ids = list(assigned)
        for ch in channels:
            uploads = "UU" + ch[2:] if ch.startswith("UC") else ch
            try:
                ids += yt_playlist_video_ids(uploads, key)
            except RuntimeError as e:
                log("chaîne ignorée", ch, "→", e)
        items = yt_videos(list(dict.fromkeys(ids)), key)

    exclude = [w.lower() for w in yc.get("exclude_keywords", [])]
    overrides = {k: v for k, v in (cfg.get("lang_overrides") or {}).items() if v in LANGS}
    out = []
    for vid, v in items.items():
        if any(w in v["title"].lower() for w in exclude):
            continue
        if yc.get("min_duration") and v["duration"] and v["duration"] < yc["min_duration"]:
            continue  # écarte les Shorts / teasers
        lang, typ, artist = assigned.get(vid, (None, None, None))
        src = "playlist" if lang else ""
        if not lang:
            lang, src = detect_lang_full(v["title"], v.get("desc", ""), v.get("audio", ""), default_lang)
        if vid in overrides:
            lang, src = overrides[vid], "manuel"
        typ = typ or detect_type(v["title"], duration=v.get("duration", 0))
        artist = artist or chan_artist.get(v.get("channel", "")) or re.sub(r"\s*-\s*Topic$", "", v.get("channel_title", "")) or cfg.get("artist", "OULBIG")
        v = {k: x for k, x in v.items() if k not in ("desc", "audio", "channel", "channel_title")}
        out.append({"source": "youtube", "lang": lang, "lang_source": src, "type": typ, "artist": artist, **v})
    log(f"YouTube : {len(out)} vidéos retenues")
    return out


# ---------------------------------------------------------------- Spotify
def spotify_token():
    cid, sec = os.environ.get("SPOTIFY_CLIENT_ID"), os.environ.get("SPOTIFY_CLIENT_SECRET")
    if not cid or not sec:
        return None
    auth = base64.b64encode(f"{cid}:{sec}".encode()).decode()
    data = http_json("https://accounts.spotify.com/api/token",
                     headers={"Authorization": f"Basic {auth}", "Content-Type": "application/x-www-form-urlencoded"},
                     data=b"grant_type=client_credentials")
    return data["access_token"]


def sp_get(url, tok):
    return http_json(url, headers={"Authorization": f"Bearer {tok}"})


def collect_spotify(cfg):
    sc = cfg.get("spotify", {})
    arts = artist_table(cfg)
    pairs = [(aid, name) for name, a in arts.items() for aid in a.get("spotify", [])]
    if not (pairs or sc.get("artist_ids") or sc.get("shows") or sc.get("artist_name")):
        return []
    try:
        tok = spotify_token()
    except RuntimeError as e:
        log("Spotify : authentification refusée →", e); return []
    if not tok:
        log("Spotify : identifiants absents, étape ignorée."); return []
    market, default_lang, out = sc.get("market", "MA"), cfg.get("default_lang", "fr"), []
    album_lang = {a["id"]: a.get("lang") for a in sc.get("albums", [])}

    artist_ids = [aid for aid, _ in pairs] or list(sc.get("artist_ids", []))
    name_of = dict(pairs)
    if not artist_ids and sc.get("artist_name"):
        # Retrouve la page artiste à partir du nom (le lien « user » n'est pas une page artiste)
        name = sc["artist_name"].strip().lower()
        q = urllib.parse.urlencode({"q": f'artist:"{sc["artist_name"]}"', "type": "artist", "market": market, "limit": 10})
        try:
            found = sp_get("https://api.spotify.com/v1/search?" + q, tok).get("artists", {}).get("items", [])
            exact = [a for a in found if a.get("name", "").strip().lower() == name]
            if exact:
                artist_ids = [a["id"] for a in exact]
                log("Spotify : artiste trouvé →", ", ".join(f"{a['name']} ({a['id']})" for a in exact),
                    "— copie cet ID dans spotify.artist_ids pour figer le choix")
            else:
                log(f"Spotify : aucun artiste nommé exactement « {sc['artist_name']} »")
        except RuntimeError as e:
            log("Spotify : recherche d'artiste impossible →", e)

    for aid in artist_ids:
        url = f"https://api.spotify.com/v1/artists/{aid}/albums?include_groups=album,single&market={market}&limit=50"
        while url:
            try:
                page = sp_get(url, tok)
            except RuntimeError as e:
                log("Spotify artiste ignoré →", e); break
            for al in page.get("items", []):
                img = (al.get("images") or [{}])[0].get("url", "")
                lang_al = album_lang.get(al["id"]) or detect_lang(al["name"], default_lang)
                turl = f"https://api.spotify.com/v1/albums/{al['id']}/tracks?market={market}&limit=50"
                while turl:
                    tp = sp_get(turl, tok)
                    for t in tp.get("items", []):
                        out.append({"source": "spotify", "type": "song", "artist": name_of.get(aid, cfg.get("artist", "OULBIG")),
                                    "lang": album_lang.get(al["id"]) or detect_lang(t["name"], lang_al),
                                    "id": t["id"], "title": t["name"], "published": al.get("release_date", ""),
                                    "duration": round(t.get("duration_ms", 0) / 1000), "thumb": img,
                                    "url": t["external_urls"]["spotify"]})
                    turl = tp.get("next")
            url = page.get("next")

    for sh in sc.get("shows", []):
        url = f"https://api.spotify.com/v1/shows/{sh['id']}/episodes?market={market}&limit=50"
        while url:
            try:
                page = sp_get(url, tok)
            except RuntimeError as e:
                log("Spotify podcast ignoré →", e); break
            for ep in page.get("items", []) or []:
                if not ep:
                    continue
                out.append({"source": "spotify", "type": "podcast", "artist": sh.get("artist", cfg.get("artist", "OULBIG")), "lang": sh.get("lang") or detect_lang(ep["name"], default_lang),
                            "id": ep["id"], "title": ep["name"], "published": ep.get("release_date", ""),
                            "duration": round(ep.get("duration_ms", 0) / 1000),
                            "thumb": (ep.get("images") or [{}])[0].get("url", ""), "url": ep["external_urls"]["spotify"]})
            url = page.get("next")
    log(f"Spotify : {len(out)} titres/épisodes")
    return out


# ---------------------------------------------------------------- Podcasts RSS
def collect_rss(cfg):
    out = []
    ns = {"itunes": "http://www.itunes.com/dtds/podcast-1.0.dtd"}
    for feed in cfg.get("podcast_rss", []):
        try:
            root = ET.fromstring(http_text(feed["url"]))
        except Exception as e:
            log("flux RSS ignoré", feed.get("url"), "→", e); continue
        for it in root.iter("item"):
            title = (it.findtext("title") or "").strip()
            enc = it.find("enclosure")
            out.append({"source": "rss", "type": "podcast", "artist": feed.get("artist", cfg.get("artist", "OULBIG")), "lang": feed.get("lang") or detect_lang(title, cfg.get("default_lang", "fr")),
                        "id": it.findtext("guid") or title, "title": title, "published": it.findtext("pubDate") or "",
                        "duration": hms_duration(it.findtext("itunes:duration", namespaces=ns)),
                        "url": it.findtext("link") or "", "audio": enc.get("url") if enc is not None else "", "thumb": ""})
    if out:
        log(f"RSS podcasts : {len(out)} épisodes")
    return out


# ---------------------------------------------------------------- fusion
def merge(yt, sp, rss, cfg, previous):
    default_artist = cfg.get("artist", "OULBIG")
    entries = {}

    def slot(lang, typ, title, artist, src=""):
        k = (lang, typ, artist.lower(), norm_key(title))
        if k not in entries:
            entries[k] = {"lang": lang, "lang_source": src, "type": typ, "artist": artist, "title": clean_title(title, artist),
                          "published": "", "duration": 0, "thumb": "", "youtube": None, "spotify": None, "rss": None}
        return entries[k]

    for v in yt:
        e = slot(v["lang"], v["type"], v["title"], v.get("artist", default_artist), v.get("lang_source", ""))
        if e["youtube"] and e["youtube"]["views"] >= v["views"]:
            continue  # garde la version la plus vue (clip officiel vs doublon)
        e["youtube"] = {"id": v["id"], "url": f"https://www.youtube.com/watch?v={v['id']}", "views": v["views"], "embeddable": v["embeddable"]}
        e["duration"] = v["duration"] or e["duration"]
        e["thumb"] = v["thumb"] or e["thumb"]
        e["published"] = max(e["published"], v["published"][:10])

    yt_by_title = {(e["type"], e["artist"].lower(), norm_key(e["title"])): e for e in entries.values()}
    for s_ in sp:
        art = s_.get("artist", default_artist)
        e = yt_by_title.get((s_["type"], art.lower(), norm_key(s_["title"]))) or slot(s_["lang"], s_["type"], s_["title"], art, "spotify")
        e["spotify"] = {"id": s_["id"], "url": s_["url"]}
        e["duration"] = e["duration"] or s_["duration"]
        e["thumb"] = e["thumb"] or s_["thumb"]
        e["published"] = e["published"] or s_["published"][:10]

    for r in rss:
        art = r.get("artist", default_artist)
        e = yt_by_title.get(("podcast", art.lower(), norm_key(r["title"]))) or slot(r["lang"], "podcast", r["title"], art, "rss")
        e["rss"] = {"url": r["url"], "audio": r["audio"]}
        e["duration"] = e["duration"] or r["duration"]

    items = list(entries.values())
    for e in items:
        e["duration"] = e["duration"] or (540 if e["type"] == "podcast" else 210)
        # identifiant stable (ne dépend plus de la langue : les likes et commentaires restent attachés)
        e["id"] = ("yt-" + e["youtube"]["id"]) if e["youtube"] else (("sp-" + e["spotify"]["id"]) if e["spotify"] else "tx-" + norm_key(e["artist"] + e["title"])[:32])
        e["rank"], e["prev_rank"], e["trend"] = 0, 0, ""

    # Hit Parade : top N par langue selon les vues YouTube, tendance vs dernière synchro
    prev = {}
    for it in (previous or {}).get("items", []):
        if it.get("rank"):
            prev[it["id"]] = it["rank"]
            if it.get("youtube"):
                prev["yt-" + it["youtube"]["id"]] = it["rank"]  # anciens identifiants (schéma 1)
    size = cfg.get("hit_parade_size", 10)
    manual = {norm_key(t): i + 1 for i, t in enumerate(cfg.get("hit_parade_manual", []))}
    for lang in LANGS:
        songs = [e for e in items if e["lang"] == lang and e["type"] == "song"]
        if manual:
            ranked = sorted([e for e in songs if norm_key(e["title"]) in manual], key=lambda e: manual[norm_key(e["title"])])
            ranked += sorted([e for e in songs if e not in ranked], key=lambda e: -(e["youtube"] or {}).get("views", 0))
        else:
            ranked = sorted(songs, key=lambda e: -(e["youtube"] or {}).get("views", 0))
        for i, e in enumerate(ranked[:size]):
            e["rank"] = i + 1
            p = prev.get(e["id"], 0)
            e["prev_rank"] = p
            e["trend"] = "new" if not p else ("up" if e["rank"] < p else "down" if e["rank"] > p else "same")

    items.sort(key=lambda e: (LANGS.index(e["lang"]) if e["lang"] in LANGS else 9, e["type"], e["rank"] or 99, e["published"]), reverse=False)
    return items


def build(items, cfg):
    langs = {}
    for lang in LANGS:
        mine = [e for e in items if e["lang"] == lang]
        langs[lang] = {
            "songs": sorted([e["id"] for e in mine if e["type"] == "song"], key=lambda i: next(x["published"] for x in mine if x["id"] == i), reverse=True),
            "podcasts": sorted([e["id"] for e in mine if e["type"] == "podcast"], key=lambda i: next(x["published"] for x in mine if x["id"] == i)),
            "hit_parade": [e["id"] for e in sorted([e for e in mine if e["rank"]], key=lambda e: e["rank"])],
        }
    return {
        "schema": 2,
        "artist": cfg.get("artist", "OULBIG"),
        "artists": {name: a.get("links", {}) for name, a in artist_table(cfg).items()},
        "generated": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "links": cfg.get("links", {}),
        "counts": {lang: {"songs": len(v["songs"]), "podcasts": len(v["podcasts"])} for lang, v in langs.items()},
        "langs": langs,
        "items": items,
    }


# ---------------------------------------------------------------- démo
def demo_sources():
    sample = {
        "fr": (["L'Étincelle", "Nuit Blanche", "Lumière du Nord", "Plus Haut", "Médina"], ["EP01 · Allumer l'étincelle", "EP10 · Devenir millionnaire"]),
        "en": (["The Spark", "Midnight Drive", "Higher", "Golden Hour"], ["EP01 · Light the Spark", "EP10 · Becoming a Millionaire"]),
        "es": (["La Chispa", "Entre Dos Mundos", "Corazón de Fuego"], ["EP01 · Encender la chispa", "EP10 · Convertirse en millonario"]),
        "ar": (["لمحة", "نور الفجر", "أعلى"], ["الحلقة 1 · أشعل الشرارة", "الحلقة 10 · كيف تصبح مليونيرًا"]),
        "ma": (["لمحة", "ليلة بيضا", "الدنيا زوينة"], ["الحلقة 1 · شعل الشرارة", "الحلقة 10 · كيفاش تولي مليونير"]),
    }
    yt, n = [], 0
    for lang, (songs, pods) in sample.items():
        for i, t in enumerate(songs):
            n += 1
            yt.append({"source": "youtube", "lang": lang, "type": "song", "id": f"DEMO{n:07d}", "title": f"OULBIG - {t} (Official Video)",
                       "published": f"2026-0{1 + i % 9}-15T10:00:00Z", "duration": 190 + 7 * i, "views": 50000 // (i + 1),
                       "thumb": "", "embeddable": True})
        for i, t in enumerate(pods):
            n += 1
            yt.append({"source": "youtube", "lang": lang, "type": "podcast", "id": f"DEMO{n:07d}", "title": t,
                       "published": f"2026-0{1 + i}-01T10:00:00Z", "duration": 545 + i * 9, "views": 1000, "thumb": "", "embeddable": True})
    return yt


# ---------------------------------------------------------------- main
def main():
    ap = argparse.ArgumentParser(description="Synchronise le catalogue OULBIG")
    ap.add_argument("--config", default="config.json")
    ap.add_argument("--out", default="catalogue.json")
    ap.add_argument("--demo", action="store_true", help="catalogue d'exemple, sans réseau")
    a = ap.parse_args()

    with open(a.config, encoding="utf-8") as f:
        cfg = json.load(f)
    ARTIST_NAMES[:] = list(artist_table(cfg)) or ["OULBIG"]
    previous = None
    if os.path.exists(a.out):
        try:
            with open(a.out, encoding="utf-8") as f:
                previous = json.load(f)
        except Exception:
            pass

    if a.demo:
        yt, sp, rss = demo_sources(), [], []
    else:
        yt, sp, rss = collect_youtube(cfg), collect_spotify(cfg), collect_rss(cfg)
    if not (yt or sp or rss):
        log("Aucun contenu récupéré : catalogue existant conservé.")
        sys.exit(1)

    cat = build(merge(yt, sp, rss, cfg, previous), cfg)
    os.makedirs(os.path.dirname(os.path.abspath(a.out)), exist_ok=True)
    with open(a.out, "w", encoding="utf-8") as f:
        json.dump(cat, f, ensure_ascii=False, indent=1)
    for lang, c in cat["counts"].items():
        log(f"{lang.upper()} : {c['songs']} titres · {c['podcasts']} podcasts")
    log(f"écrit → {a.out}")


if __name__ == "__main__":
    main()
