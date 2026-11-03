"""Diagnostic : ce que l'API YouTube renvoie pour chaque source du catalogue (aucune écriture)."""
import json, os, urllib.parse, urllib.request
K = os.environ["YT_API_KEY"]
def get(path, **q):
    q["key"] = K
    try:
        with urllib.request.urlopen("https://www.googleapis.com/youtube/v3/" + path + "?" + urllib.parse.urlencode(q), timeout=30) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        return {"error": e.code, "body": e.read().decode()[:200]}
def pl_count(pid):
    n, tok, pages = 0, "", 0
    while True:
        d = get("playlistItems", part="contentDetails", playlistId=pid, maxResults=50, pageToken=tok)
        if "error" in d: return f"ERREUR {d['error']} après {n}"
        n += len(d.get("items", [])); pages += 1
        tok = d.get("nextPageToken")
        if not tok: return f"{n} ids ({pages} pages, total annoncé {d.get('pageInfo',{}).get('totalResults')})"
for ch in ["UCmLAQNmYAFjFfYDmnN36LHg", "UCpWPGzDk5xXbE2fc48AeM8Q", "UCqLXTRscQHOkMjI8RZzU7KQ", "UCduWsxZ5-V1PXxAHa2NDe4Q"]:
    d = get("channels", part="snippet,statistics,contentDetails", id=ch)
    it = (d.get("items") or [{}])[0]
    print("==", ch, it.get("snippet", {}).get("title"), it.get("snippet", {}).get("customUrl"), "videoCount:", it.get("statistics", {}).get("videoCount"))
    up = it.get("contentDetails", {}).get("relatedPlaylists", {}).get("uploads")
    print("   uploads", up, "→", pl_count(up) if up else "aucun")
    p = get("playlists", part="snippet,contentDetails", channelId=ch, maxResults=50)
    pls = p.get("items", [])
    print("   playlists:", len(pls), p.get("error", ""))
    for x in pls[:30]:
        print("     ", x["id"], x["contentDetails"]["itemCount"], x["snippet"]["title"][:60])
    s = get("search", part="id", channelId=ch, type="video", maxResults=50, order="date")
    print("   search:", s.get("pageInfo", {}).get("totalResults"), "résultats", s.get("error", ""))
for h in ["@TALIM4Art", "@OULBIG"]:
    d = get("channels", part="snippet,statistics", forHandle=h)
    for it in d.get("items", []):
        print("handle", h, "→", it["id"], it["snippet"]["title"], it["statistics"].get("videoCount"))
print("radio playlist →", pl_count("PLx1bfRrl4XWPEGJS3QzmR_NUvmIVtahKX"))
