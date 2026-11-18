import json, os, re, urllib.parse, urllib.request
K = os.environ["YT_API_KEY"]
def get(path, **q):
    q["key"] = K
    try:
        with urllib.request.urlopen("https://www.googleapis.com/youtube/v3/" + path + "?" + urllib.parse.urlencode(q), timeout=30) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        return {"error": e.code, "body": e.read().decode()[:300]}
for pid in ["PLG8nCC0ZwJyw", "PLeH99dOLMlRE"]:
    p = get("playlists", part="snippet,contentDetails,status", id=pid)
    print("==", pid, json.dumps(p)[:300])
    d = get("playlistItems", part="contentDetails,snippet,status", playlistId=pid, maxResults=50)
    if "error" in d:
        print("  ERREUR", d); continue
    ids = [it["contentDetails"]["videoId"] for it in d.get("items", [])]
    v = get("videos", part="snippet,contentDetails,status", id=",".join(ids))
    for it in v.get("items", []):
        print("  ", it["id"], it["contentDetails"]["duration"], it["status"]["privacyStatus"], it["snippet"]["channelTitle"], "|", it["snippet"]["title"][:80])
