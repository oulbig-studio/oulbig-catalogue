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
for pid in ["PLx1bfRrl4XWPEGJS3QzmR_NUvmIVtahKX", "PLRLtSY8rjZX4"]:
    ids, tok = [], ""
    while True:
        d = get("playlistItems", part="contentDetails", playlistId=pid, maxResults=50, pageToken=tok)
        if "error" in d: print("ERREUR", pid, d); break
        ids += [it["contentDetails"]["videoId"] for it in d["items"]]
        tok = d.get("nextPageToken")
        if not tok: break
    print("==", pid, len(ids))
    for i in range(0, len(ids), 50):
        for v in get("videos", part="snippet,contentDetails,status", id=",".join(ids[i:i+50])).get("items", []):
            print("  ", v["id"], v["contentDetails"]["duration"], v["status"]["privacyStatus"], v["snippet"]["publishedAt"][:10], cat.get(v["id"], "-"), "|", v["snippet"]["title"][:75])
