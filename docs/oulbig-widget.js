/*!
 * OULBIG Widget — affiche musique / podcasts / Hit Parade sur n'importe quel site (HTML, Wix, Blogger…)
 *
 * <script src="https://ton-compte.github.io/oulbig-catalogue/oulbig-widget.js"
 *         data-catalogue="https://ton-compte.github.io/oulbig-catalogue/catalogue.json" defer></script>
 * <div data-oulbig="musique"   data-lang="fr" data-limite="8"></div>
 * <div data-oulbig="podcasts"  data-lang="es"></div>
 * <div data-oulbig="hitparade" data-lang="ma"></div>
 * <a   data-oulbig-lien="L'Étincelle" data-plateforme="spotify">Écouter</a>
 */
(function () {
  var me = document.currentScript;
  var URL_CAT = (me && me.getAttribute("data-catalogue")) || "https://oulbig-studio.github.io/oulbig-catalogue/catalogue.json";
  var ARROW = { up: "▲", down: "▼", same: "=", new: "NEW" };

  function esc(s) { return String(s || "").replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); }
  function dur(s) { s = s | 0; return Math.floor(s / 60) + ":" + ("0" + (s % 60)).slice(-2); }
  function btns(i) {
    var h = "";
    if (i.youtube && i.youtube.url) h += '<a class="ob-btn ob-yt" href="' + esc(i.youtube.url) + '" target="_blank" rel="noopener">YouTube</a>';
    if (i.spotify && i.spotify.url) h += '<a class="ob-btn ob-sp" href="' + esc(i.spotify.url) + '" target="_blank" rel="noopener">Spotify</a>';
    return '<div class="ob-btns">' + h + "</div>";
  }
  function pick(cat, lang, type, limit, order) {
    var l = cat.items.filter(function (i) { return i.type === type && (lang === "all" || i.lang === lang); });
    if (order === "rank") l = l.filter(function (i) { return i.rank; }).sort(function (a, b) { return a.rank - b.rank; });
    else l.sort(function (a, b) { return (b.published || "").localeCompare(a.published || ""); });
    return l.slice(0, limit);
  }
  var CSS = ".ob-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px}.ob-card{display:flex;flex-direction:column;gap:6px}.ob-art{aspect-ratio:16/9;border-radius:12px;overflow:hidden;background:linear-gradient(135deg,#FF3D6E,#FFB347)}.ob-art img{width:100%;height:100%;object-fit:cover;display:block}.ob-card h3{font-size:1rem;margin:2px 0 0}.ob-meta{display:block;font-size:.8rem;opacity:.65}.ob-btns{display:flex;gap:6px;flex-wrap:wrap}.ob-btn{padding:5px 12px;border-radius:999px;font-size:.8rem;font-weight:600;text-decoration:none;border:1px solid currentColor}.ob-yt{color:#E3134B}.ob-sp{color:#17A94F}.ob-list{list-style:none;margin:0;padding:0}.ob-list li{display:flex;align-items:center;gap:12px;padding:10px 0;border-top:1px solid rgba(127,127,127,.25)}.ob-list li>div:first-of-type{flex:1;min-width:0}.ob-rank{font-size:1.4rem;font-weight:900;width:32px;text-align:center}.ob-trend{font-size:.75rem;font-weight:700;width:34px;text-align:center}.ob-up{color:#0E9C66}.ob-down{color:#D63636}.ob-new{color:#EE8A00}";

  function render(cat) {
    var st = document.createElement("style"); st.textContent = CSS; document.head.appendChild(st);
    document.querySelectorAll("[data-oulbig]").forEach(function (el) {
      var kind = el.getAttribute("data-oulbig"), lang = el.getAttribute("data-lang") || "all";
      var lim = +el.getAttribute("data-limite") || (kind === "musique" ? 12 : 10), h = "";
      if (lang === "ar" || lang === "ma") el.dir = "rtl";
      if (kind === "musique") {
        h = '<div class="ob-grid">' + pick(cat, lang, "song", lim).map(function (i) {
          return '<article class="ob-card"><div class="ob-art">' + (i.thumb ? '<img src="' + esc(i.thumb) + '" alt="" loading="lazy">' : "") + "</div><h3>" + esc(i.title) + '</h3><span class="ob-meta">OULBIG · ' + i.lang.toUpperCase() + "</span>" + btns(i) + "</article>";
        }).join("") + "</div>";
      } else if (kind === "podcasts") {
        h = '<ol class="ob-list">' + pick(cat, lang, "podcast", lim).map(function (i) {
          return "<li><div><strong>" + esc(i.title) + '</strong><span class="ob-meta">' + esc(i.published) + " · " + dur(i.duration) + "</span></div>" + btns(i) + "</li>";
        }).join("") + "</ol>";
      } else if (kind === "hitparade") {
        h = '<ol class="ob-list">' + pick(cat, lang === "all" ? "fr" : lang, "song", lim, "rank").map(function (i) {
          return '<li><span class="ob-rank">' + i.rank + "</span><div><strong>" + esc(i.title) + '</strong></div><span class="ob-trend ob-' + i.trend + '">' + (ARROW[i.trend] || "") + "</span>" + btns(i) + "</li>";
        }).join("") + "</ol>";
      }
      el.innerHTML = h;
    });
    document.querySelectorAll("[data-oulbig-lien]").forEach(function (a) {
      var t = a.getAttribute("data-oulbig-lien").toLowerCase(), p = a.getAttribute("data-plateforme") || "youtube";
      var it = cat.items.find(function (i) { return i.title.toLowerCase() === t && i[p] && i[p].url; });
      if (it) { a.href = it[p].url; a.target = "_blank"; a.rel = "noopener"; }
    });
  }

  function go() {
    fetch(URL_CAT, { cache: "no-cache" }).then(function (r) { return r.json(); }).then(render)
      .catch(function (e) { console.warn("OULBIG widget : catalogue introuvable", e); });
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", go); else go();
})();
