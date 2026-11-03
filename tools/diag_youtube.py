"""Diagnostic : statut actuel des vidéos sorties du catalogue + titres TALIM trouvés par la recherche."""
import json, os, urllib.parse, urllib.request, collections
K = os.environ["YT_API_KEY"]
def get(path, **q):
    q["key"] = K
    with urllib.request.urlopen("https://www.googleapis.com/youtube/v3/" + path + "?" + urllib.parse.urlencode(q), timeout=30) as r:
        return json.load(r)
lost = json.load(open("tools/lost_ids.json"))
ids = list(lost); found = {}
for i in range(0, len(ids), 50):
    for v in get("videos", part="snippet,status", id=",".join(ids[i:i+50])).get("items", []):
        found[v["id"]] = (v["status"]["privacyStatus"], v["snippet"]["channelTitle"], v["snippet"]["channelId"])
c = collections.Counter()
for vid, meta in lost.items():
    st = found.get(vid, ("supprimée", "-", "-"))
    c[(meta[0], meta[1], st[0], st[1], st[2])] += 1
for k, n in c.most_common(): print(n, k)
print("--- TALIM Topic via search")
tok, n = "", 0
while True:
    d = get("search", part="snippet", channelId="UCqLXTRscQHOkMjI8RZzU7KQ", type="video", maxResults=50, order="date", pageToken=tok)
    for it in d.get("items", []):
        n += 1; print(" ", it["id"]["videoId"], it["snippet"]["publishedAt"][:10], it["snippet"]["title"][:60])
    tok = d.get("nextPageToken")
    if not tok or n > 300: break
print("total", n)
print("--- TALIM Art uploads")
for it in get("playlistItems", part="snippet", playlistId="UUduWsxZ5-V1PXxAHa2NDe4Q", maxResults=50).get("items", []):
    print(" ", it["snippet"]["publishedAt"][:10], it["snippet"]["title"][:60])
print("--- OULBIG main : 15 dernières")
for it in get("playlistItems", part="snippet", playlistId="UUmLAQNmYAFjFfYDmnN36LHg", maxResults=15).get("items", []):
    print(" ", it["snippet"]["publishedAt"][:10], it["snippet"]["title"][:60])
