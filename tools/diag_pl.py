import json, os, urllib.parse, urllib.request
K = os.environ["YT_API_KEY"]
def get(path, **q):
    q["key"] = K
    try:
        with urllib.request.urlopen("https://www.googleapis.com/youtube/v3/" + path + "?" + urllib.parse.urlencode(q), timeout=30) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        return {"error": e.code, "body": e.read().decode()[:300]}
cat = {i["youtube"]["id"]: (i["type"], i["lang"]) for i in json.load(open("docs/catalogue.json"))["items"]}
pid = "PLULibU4b2T5Y"
print(json.dumps(get("playlists", part="snippet", id=pid).get("items", [{}])[0].get("snippet", {}).get("title")))
ids, tok = [], ""
while True:
    d = get("playlistItems", part="contentDetails", playlistId=pid, maxResults=50, pageToken=tok)
    if "error" in d: print("ERREUR", d); break
    ids += [it["contentDetails"]["videoId"] for it in d["items"]]
    tok = d.get("nextPageToken")
    if not tok: break
print("==", len(ids))
for i in range(0, len(ids), 50):
    for v in get("videos", part="snippet,contentDetails,status", id=",".join(ids[i:i+50])).get("items", []):
        s = v["snippet"]
        print("  ", v["id"], v["contentDetails"]["duration"], v["status"]["privacyStatus"], s["publishedAt"][:10], cat.get(v["id"], "-"), s.get("defaultAudioLanguage", "?"), "|", s["title"][:80], "|", s.get("description", "")[:60].replace("\n", " "))
