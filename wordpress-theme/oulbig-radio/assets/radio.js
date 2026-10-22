/* OULBIG Radio v2 — lecteur, grille synchronisée, comptes (WordPress) ou mode démo. */
(function () {
'use strict';

/* =====================================================================
   1. Réglages (valeurs par défaut, remplacées par window.ORAD_CONFIG)
   ===================================================================== */
const DEFAULTS = {
  mode: 'demo',                     // 'wp' sur le site WordPress (comptes réels)
  skin: 'auto',                     // 'sombre' | 'clair' | 'auto'
  catalogueUrl: 'https://oulbig-studio.github.io/oulbig-catalogue/catalogue.json',
  site: 'https://radio.almoqayada.com',
  rest: '', nonce: '', user: null, loginUrl: '', registerUrl: '', logoutUrl: '',
  hitMode: 'mixte',                 // 'mixte' | 'votes' | 'vues'
  adEvery: 6,                       // une pub vidéo toutes les N pistes (si une vidéo est renseignée)
  promoEvery: 3,                    // carte partenaire dans le lecteur toutes les N pistes
  socials: {
    instagram: 'https://www.instagram.com/oulbig_radio',
    youtube: 'https://youtube.com/playlist?list=PLx1bfRrl4XWPEGJS3QzmR_NUvmIVtahKX',
    facebook: 'https://www.facebook.com/share/18AcNQdDYA/',
    x: '', tiktok: '',
    spotify: 'https://open.spotify.com/artist/2DHkzizRab1JFRb61bQAUi'
  },
  artists: [
    { name: 'OULBIG', color: '#FF3D6E', youtube: 'https://www.youtube.com/@OULBIG', youtube2: 'https://www.youtube.com/channel/UCpWPGzDk5xXbE2fc48AeM8Q', spotify: 'https://open.spotify.com/artist/2DHkzizRab1JFRb61bQAUi',
      tagline: { fr: "Fondateur de la radio : musique et podcasts L'Étincelle en 5 langues", en: "Radio founder: music and L'Étincelle podcasts in 5 languages", es: "Fundador de la radio: música y pódcasts L'Étincelle en 5 idiomas", ar: 'مؤسس الإذاعة: موسيقى وبودكاست لمحة بخمس لغات', ma: 'مؤسس الراديو: موسيقى وبودكاست لمحة بخمسة لغات' } },
    { name: 'TALIM', color: '#9D8BFF', youtube: 'https://www.youtube.com/@TALIM4Art', youtube2: 'https://www.youtube.com/channel/UCqLXTRscQHOkMjI8RZzU7KQ', spotify: 'https://open.spotify.com/artist/2FAKBSpY965IRet4aXTmEm',
      tagline: { fr: 'Nouvelle voix, nouveau son', en: 'New voice, new sound', es: 'Nueva voz, nuevo sonido', ar: 'صوت جديد، إيقاع جديد', ma: 'صوت جديد، ستيل جديد' } }
  ],
  sponsors: [
    { name: 'Musicology', url: 'https://musicology.almoqayada.com', accent: '#FF3D6E',
      tagline: { fr: 'La chanson de tes rêves, écrite et produite pour toi', en: 'The song of your dreams, written and produced for you', es: 'La canción de tus sueños, escrita y producida para ti', ar: 'أغنية أحلامك، نكتبها ونلحّنها لك', ma: 'الغنية اللي كتحلم بيها، كنكتبوها ونصاوبوها ليك' } },
    { name: 'ABSProjects', url: '', accent: '#9D8BFF',
      tagline: { fr: "Le hub créatif d'OULBIG : vidéos, audiobooks, ebooks, design", en: "OULBIG's creative hub: videos, audiobooks, ebooks, design", es: 'El hub creativo de OULBIG: vídeos, audiolibros, ebooks, diseño', ar: 'مركز OULBIG الإبداعي: فيديو، كتب صوتية، كتب رقمية، تصميم', ma: 'المركز الإبداعي ديال OULBIG: فيديوهات، كتب صوتية، كتب رقمية، ديزاين' } },
    { name: 'Almoqayada.com', url: 'https://almoqayada.com', accent: '#3DDC97',
      tagline: { fr: 'Troc et vente de biens, toutes catégories', en: 'Swap and sell goods in every category', es: 'Trueque y venta de bienes, todas las categorías', ar: 'مقايضة وبيع السلع في كل الفئات', ma: 'التبادل والبيع ديال السلعة، كاع الأصناف' } },
    { name: 'Almoqayada.pro', url: 'https://almoqayada.pro', accent: '#FFB347',
      tagline: { fr: 'Échange et vente de services entre pros et particuliers', en: 'Trade and sell services, pros and individuals', es: 'Intercambio y venta de servicios', ar: 'تبادل الخدمات وبيعها بين المهنيين والأفراد', ma: 'تبادل وبيع الخدمات بين المحترفين والناس' } }
  ],
  // Jingles (à publier sur YouTube) : renseigner l'ID ou le lien de la vidéo pour chaque langue
  jingles: { fr: { yt: '', dur: 8 }, en: { yt: '', dur: 8 }, es: { yt: '', dur: 8 }, ar: { yt: '', dur: 8 }, ma: { yt: '', dur: 8 } },
  // Pubs vidéo dans le lecteur : lang 'all' ou fr/en/es/ar/ma
  ads: [
    { label: 'OULBIG Radio', lang: 'all', yt: '', dur: 30, url: '' },
    { label: 'Musicology', lang: 'all', yt: '', dur: 30, url: 'https://musicology.almoqayada.com' }
  ]
};
const CFG = merge(DEFAULTS, window.ORAD_CONFIG || {});
// Slogans traduits et couleurs par défaut quand le site ne les précise pas
['sponsors', 'artists'].forEach((k) => (CFG[k] || []).forEach((x) => {
  const d = DEFAULTS[k].find((y) => y.name === x.name); if (!d) return;
  if (!x.tagline) x.tagline = d.tagline; if (!x.color && d.color) x.color = d.color; if (!x.accent && d.accent) x.accent = d.accent;
}));
function merge(a, b) {
  const out = Array.isArray(a) ? a.slice() : Object.assign({}, a);
  Object.keys(b || {}).forEach((k) => {
    const v = b[k];
    if (v && typeof v === 'object' && !Array.isArray(v) && a && typeof a[k] === 'object' && !Array.isArray(a[k]) && a[k] !== null) out[k] = merge(a[k], v);
    else if (v !== undefined && v !== null) out[k] = v;
  });
  return out;
}
const ytId = (s) => { s = String(s || '').trim(); if (/^[\w-]{11}$/.test(s)) return s; const m = s.match(/(?:v=|youtu\.be\/|shorts\/|embed\/|live\/)([\w-]{11})/); return m ? m[1] : ''; };

/* =====================================================================
   2. Stations, textes
   ===================================================================== */
const LANGS = ['fr', 'en', 'es', 'ar', 'ma'];
const STATIONS = {
  fr: { code: 'FR', name: 'Français', dir: 'ltr', html: 'fr', ang: 210 },
  en: { code: 'EN', name: 'English', dir: 'ltr', html: 'en', ang: 240 },
  es: { code: 'ES', name: 'Español', dir: 'ltr', html: 'es', ang: 270 },
  ar: { code: 'ع', name: 'العربية الفصحى', dir: 'rtl', html: 'ar', ang: 300 },
  ma: { code: 'د', name: 'الدارجة', dir: 'rtl', html: 'ar-MA', ang: 330 }
};
const I18N = window.ORAD_I18N || { fr: {} };
const T = (k, n) => { const s = (I18N[S.lang] && I18N[S.lang][k]) || I18N.fr[k] || k; return n === undefined ? s : s.replace('{n}', n); };
const tr = (v) => (v && typeof v === 'object') ? (v[S.lang] || v.fr || '') : (v || '');
const $ = (id) => document.getElementById(id);
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const fmt = (s) => { s = Math.max(0, Math.floor(s)); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); };
const hhmm = (ms) => new Date(ms).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
const hue = (str) => { let h = 0; for (const c of String(str)) h = (h * 31 + c.codePointAt(0)) % 360; return h; };
const store = { get(k, d) { try { const v = localStorage.getItem(k); return v ? JSON.parse(v) : d; } catch (e) { return d; } }, set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} } };

/* Échantillon affiché si le catalogue en ligne est inaccessible */
const SAMPLE = {
  fr: [["L'Étincelle", 212], ['Nuit Blanche', 198], ['Lumière du Nord', 224], ['Plus Haut', 187], ['Médina', 205]],
  en: [['The Spark', 212], ['Midnight Drive', 201], ['Higher', 188], ['Golden Hour', 215]],
  es: [['La Chispa', 212], ['Entre Dos Mundos', 226], ['Corazón de Fuego', 204], ['Sin Fronteras', 231]],
  ar: [['لمحة', 212], ['نور الفجر', 208], ['أعلى', 188], ['بلا حدود', 229]],
  ma: [['لمحة', 212], ['ليلة بيضا', 203], ['الدنيا زوينة', 197], ['بلا حدود', 229]]
};

/* =====================================================================
   3. État
   ===================================================================== */
const S = {
  lang: 'fr', mode: 'live', queue: [], qi: 0, qOff: 0, qT0: 0, playing: false,
  ytOK: false, player: null, ytState: -1, skew: 0, lastTick: Date.now(), shownKey: '', playKey: '',
  tracks: {}, db: {}, memo: {}, period: {},
  likes: {}, mine: new Set(), coms: {}, overrides: {}, extra: [], hitLikes: {},
  user: CFG.user, requested: !!(CFG.user && CFG.user.requested),
  playlists: [], pubPlaylists: [], tab: 'mine', synced: '', drawer: null, ribbon: 0, promoUntil: 0, sample: false
};
try { const h = location.hash.slice(1); if (LANGS.includes(h)) S.lang = h; else { const s = localStorage.getItem('orad-st'); if (LANGS.includes(s)) S.lang = s; } } catch (e) {}

/* =====================================================================
   4. API : WordPress (comptes réels) ou démo (stockage local)
   ===================================================================== */
const API = (CFG.mode === 'wp' && CFG.rest) ? wpApi() : demoApi();
function wpApi() {
  const base = CFG.rest.replace(/\/?$/, '/');
  const call = (path, opt) => {
    opt = opt || {};
    const headers = { 'X-WP-Nonce': CFG.nonce };
    if (opt.body) headers['Content-Type'] = 'application/json';
    return fetch(base + path, { method: opt.method || 'GET', credentials: 'same-origin', headers, body: opt.body ? JSON.stringify(opt.body) : undefined })
      .then((r) => r.json().catch(() => ({})).then((j) => { if (!r.ok) { const e = new Error(j.message || ('HTTP ' + r.status)); e.code = j.code; e.status = r.status; throw e; } return j; }));
  };
  return {
    real: true,
    bootstrap: () => call('bootstrap'),
    like: (id) => call('like', { method: 'POST', body: { track: id } }),
    comments: (id) => call('comments?track=' + encodeURIComponent(id)),
    comment: (id, text, lang) => call('comments', { method: 'POST', body: { track: id, text, lang } }),
    report: (id, lang) => call('report', { method: 'POST', body: { track: id, lang } }),
    playlists: () => call('playlists'),
    publicPlaylists: () => call('playlists/public'),
    savePlaylist: (p) => call('playlists', { method: 'POST', body: p }),
    deletePlaylist: (id) => call('playlists/' + encodeURIComponent(id), { method: 'DELETE' }),
    requestAccess: () => call('access', { method: 'POST' }),
    submit: (d) => call('submit', { method: 'POST', body: d })
  };
}
function demoApi() {
  const K = 'orad-demo-';
  const get = (k, d) => store.get(K + k, d), set = (k, v) => store.set(K + k, v);
  const ok = (v) => Promise.resolve(v);
  const user = () => get('user', null);
  return {
    real: false,
    bootstrap: () => {
      const mine = get('likes', []), likes = {}; mine.forEach((id) => { likes[id] = 1; });
      const coms = {}; Object.entries(get('coms', {})).forEach(([id, l]) => { coms[id] = l.length; });
      return ok({ user: user(), likes, mine, comments: coms, overrides: get('over', {}), extra: [], hitLikes: likes });
    },
    like: (id) => { const l = new Set(get('likes', [])); const on = !l.has(id); on ? l.add(id) : l.delete(id); set('likes', [...l]); return ok({ liked: on, count: on ? 1 : 0 }); },
    comments: (id) => ok(get('coms', {})[id] || []),
    comment: (id, text, lang) => { const all = get('coms', {}); const c = { id: Date.now(), name: (user() || {}).name || '—', text, lang: lang || '', date: new Date().toISOString() }; (all[id] = all[id] || []).push(c); set('coms', all); return ok(c); },
    report: (id, lang) => { const o = get('over', {}); o[id] = lang; set('over', o); return ok({ applied: true, lang }); },
    playlists: () => ok(get('pls', [])),
    publicPlaylists: () => ok(get('pls', []).filter((p) => p.public).map((p) => Object.assign({ owner: (user() || {}).name || '' }, p))),
    savePlaylist: (p) => { const l = get('pls', []); if (!p.id) p.id = 'p' + Date.now(); const i = l.findIndex((x) => x.id === p.id); i >= 0 ? (l[i] = p) : l.push(p); set('pls', l); return ok(p); },
    deletePlaylist: (id) => { set('pls', get('pls', []).filter((p) => p.id !== id)); return ok({ deleted: true }); },
    requestAccess: () => { set('req', true); return ok({ requested: true }); },
    submit: () => ok({ status: 'pending' }),
    demoLogin: (name) => { const u = { id: 1, name, canPublish: true, demo: true }; set('user', u); return u; },
    demoLogout: () => { set('user', null); }
  };
}

/* =====================================================================
   5. Catalogue → pistes par station
   ===================================================================== */
function ingest(feed) {
  const tracks = {};
  (feed && feed.items || []).forEach((i) => {
    if (!i.youtube || !i.youtube.id || i.youtube.embeddable === false) return;
    const id = 'yt-' + i.youtube.id;
    tracks[id] = { id, title: i.title, artist: i.artist || feed.artist || 'OULBIG', lang: i.lang, type: i.type === 'podcast' ? 'podcast' : 'song',
      yt: i.youtube.id, dur: i.duration || 210, views: i.youtube.views || 0, sp: (i.spotify && i.spotify.url) || '', published: i.published || '', trend: i.trend || '' };
  });
  return tracks;
}
function addExtra(list) {
  (list || []).forEach((e) => {
    const yt = ytId(e.yt || e.url); if (!yt) return;
    const id = 'yt-' + yt;
    if (!S.tracks[id]) S.tracks[id] = { id, title: e.title, artist: e.artist || 'OULBIG', lang: e.lang, type: e.type === 'podcast' ? 'podcast' : 'song', yt, dur: +e.dur || 210, views: 0, sp: e.sp || '', published: e.date || '', trend: 'new' };
  });
}
function sampleTracks() {
  const t = {};
  LANGS.forEach((l) => SAMPLE[l].forEach(([title, dur], i) => { const id = 'demo-' + l + i; t[id] = { id, title, artist: 'OULBIG', lang: l, type: 'song', yt: '', dur, views: 100 - i * 10, sp: '', published: '2026-0' + (i + 1) + '-01', trend: 'same' }; }));
  return t;
}
const langOf = (t) => (S.overrides[t.id] && LANGS.includes(S.overrides[t.id])) ? S.overrides[t.id] : t.lang;
function score(t) {
  const v = S.hitLikes[t.id] || 0;
  if (CFG.hitMode === 'votes') return v * 1e6 + (t.views || 0) / 1e6;
  if (CFG.hitMode === 'vues') return t.views || 0;
  return (t.views || 0) + v * 100;
}
function buildDB() {
  const db = {};
  LANGS.forEach((l) => { db[l] = { songs: [], pods: [], hits: [] }; });
  Object.values(S.tracks).forEach((t) => { const l = langOf(t); if (db[l]) (t.type === 'podcast' ? db[l].pods : db[l].songs).push(t); });
  const byId = (a, b) => (a.id < b.id ? -1 : 1);
  LANGS.forEach((l) => {
    const d = db[l];
    if (!d.songs.length) { d.songs = Object.values(sampleTracks()).filter((t) => t.lang === l); }
    d.songs.sort((a, b) => (b.published || '').localeCompare(a.published || '') || byId(a, b));
    d.pods.sort((a, b) => (a.published || '').localeCompare(b.published || '') || byId(a, b));
    d.hits = d.songs.slice().sort((a, b) => score(b) - score(a) || byId(a, b)).slice(0, 10);
  });
  S.db = db; S.memo = {}; S.period = {};
}
const rankOf = (t) => { const l = S.db[langOf(t)]; const i = l ? l.hits.indexOf(t) : -1; return i + 1; };

/* =====================================================================
   6. Grille : podcast / titre … jingle, Hit Parade 10 → 1, pubs vidéo
   ===================================================================== */
const EPOCH = Date.UTC(2026, 9, 1, 0, 0, 0);
function jingle(l) { const j = CFG.jingles[l] || {}; const yt = ytId(j.yt); return yt ? { id: 'jingle-' + l, special: 'jingle', title: T('jingle_title'), artist: 'OULBIG Radio', yt, dur: +j.dur || 8 } : null; }
function adsFor(l) { return (CFG.ads || []).map((a, i) => ({ a, i })).filter(({ a }) => ytId(a.yt) && (a.lang === 'all' || a.lang === l)).map(({ a, i }) => ({ id: 'ad-' + i, special: 'ad', title: a.label, artist: T('kind_ad'), yt: ytId(a.yt), dur: +a.dur || 30, url: a.url || '' })); }
function cycle(l, k) {
  const d = S.db[l], n = d.songs.length, km = ((k % n) + n) % n, key = l + '|' + km;
  if (S.memo[key]) return S.memo[key];
  const content = [];
  if (d.pods.length) d.pods.forEach((p, i) => { content.push({ kind: 'pod', ref: p }); content.push({ kind: 'song', ref: d.songs[(km * d.pods.length + i) % n] }); });
  else for (let i = 0; i < Math.min(8, n); i++) content.push({ kind: 'song', ref: d.songs[(km * 8 + i) % n] });
  const J = jingle(l), ads = adsFor(l), every = Math.max(2, +CFG.adEvery || 6);
  const items = []; let ai = km;
  if (J) items.push({ kind: 'jingle', ref: J });
  content.forEach((it, i) => { items.push(it); if (ads.length && (i + 1) % every === 0) items.push({ kind: 'ad', ref: ads[(ai++) % ads.length] }); });
  if (J) items.push({ kind: 'jingle', ref: J });
  d.hits.slice().reverse().forEach((h) => items.push({ kind: 'hit', ref: h }));
  if (ads.length) items.push({ kind: 'ad', ref: ads[(ai++) % ads.length] });
  let t = 0; items.forEach((it) => { it.start = t; t += it.ref.dur; });
  return (S.memo[key] = { items, dur: t });
}
function locate(l, ms) {
  const n = S.db[l].songs.length;
  if (!S.period[l]) { const durs = []; for (let k = 0; k < n; k++) durs.push(cycle(l, k).dur); S.period[l] = { durs, total: durs.reduce((a, b) => a + b, 0) }; }
  const P = S.period[l]; let el = Math.max(0, (ms - EPOCH) / 1000);
  const full = Math.floor(el / P.total); el -= full * P.total;
  let k = 0; while (k < n - 1 && el >= P.durs[k]) { el -= P.durs[k]; k++; }
  const c = cycle(l, k);
  let idx = c.items.findIndex((it) => el >= it.start && el < it.start + it.ref.dur); if (idx < 0) idx = c.items.length - 1;
  return { l, k: full * n + k, c, idx, it: c.items[idx], off: el - c.items[idx].start, cyc: el };
}
function upcoming(loc, count) {
  const out = []; let k = loc.k, idx = loc.idx, wait = loc.it.ref.dur - loc.off, c = loc.c;
  while (out.length < count) { idx++; if (idx >= c.items.length) { k++; c = cycle(loc.l, k); idx = 0; } out.push({ it: c.items[idx], wait }); wait += c.items[idx].ref.dur; }
  return out;
}
const vnow = () => Date.now() + S.skew * 1000;

/* =====================================================================
   7. Rendu
   ===================================================================== */
function applyLang() {
  const st = STATIONS[S.lang];
  document.documentElement.lang = st.html; document.documentElement.dir = st.dir;
  document.querySelectorAll('[data-i18n]').forEach((el) => { el.textContent = T(el.dataset.i18n); });
  document.querySelectorAll('[data-i18n-title]').forEach((el) => { el.title = T(el.dataset.i18nTitle); el.setAttribute('aria-label', T(el.dataset.i18nTitle)); });
  document.querySelectorAll('[data-i18n-ph]').forEach((el) => { el.placeholder = T(el.dataset.i18nPh); });
  $('station-name').textContent = st.name;
  $('station-sub').textContent = T('station_sub');
  $('grid-rule').textContent = T('rule');
  $('hit-sub').textContent = T('hit_sub_' + CFG.hitMode);
  $('foot-tag').textContent = T('rule');
  $('play-lbl').textContent = S.playing ? T('pause') : (S.mode === 'live' ? T('listen') : T('pl_play'));
  showSync();
}
function renderDial() {
  const box = $('dial-btns');
  box.innerHTML = LANGS.map((l) => {
    const a = STATIONS[l].ang * Math.PI / 180, x = 50 + 44 * Math.cos(a), y = 50 + 44 * Math.sin(a);
    return `<button class="st-btn" role="radio" aria-checked="${l === S.lang}" data-l="${l}" style="left:${x}%;top:${y}%" title="${esc(STATIONS[l].name)}">${STATIONS[l].code}</button>`;
  }).join('');
  $('needle').style.transform = `rotate(${STATIONS[S.lang].ang - 270}deg)`;
}
const SOC = [['instagram', 'i-ig', 'Instagram'], ['youtube', 'i-yt', 'YouTube'], ['facebook', 'i-fb', 'Facebook'], ['x', 'i-x', 'X'], ['tiktok', 'i-tt', 'TikTok'], ['spotify', 'i-sp', 'Spotify']];
function renderSocials() {
  const html = SOC.map(([k, ic, name]) => {
    const u = CFG.socials[k];
    return u ? `<a class="soc" href="${esc(u)}" target="_blank" rel="noopener" title="${name}" aria-label="${name}"><svg><use href="#${ic}"/></svg></a>`
             : `<span class="soc off" title="${name} — ${esc(T('soon'))}" aria-label="${name} — ${esc(T('soon'))}"><svg><use href="#${ic}"/></svg></span>`;
  }).join('');
  $('socials-top').innerHTML = html; $('socials-foot').innerHTML = html;
}
function renderAccount() {
  const box = $('account');
  if (S.user) {
    const ini = esc((S.user.name || '?').trim().charAt(0).toUpperCase());
    box.innerHTML = `<button class="avatar" id="acc-btn" title="${esc(S.user.name)}" aria-label="${esc(S.user.name)}">${ini}</button>`;
  } else {
    box.innerHTML = `<a class="btn ghost" href="${esc(CFG.loginUrl || '#')}" data-auth="login">${esc(T('login'))}</a><a class="btn solid" href="${esc(CFG.registerUrl || '#')}" data-auth="signup">${esc(T('signup'))}</a>`;
  }
  $('join-wrap').hidden = !!S.user;
  $('join-signup').href = CFG.registerUrl || '#'; $('join-login').href = CFG.loginUrl || '#';
  $('join-signup').dataset.auth = 'signup'; $('join-login').dataset.auth = 'login';
}
function renderRibbon() {
  const list = CFG.sponsors.filter((s) => s.name); if (!list.length) { $('ribbon').hidden = true; return; }
  const s = list[S.ribbon % list.length];
  const tag = s.url ? 'a' : 'div';
  const r = $('ribbon');
  r.style.setProperty('--accent', s.accent || 'var(--signal)');
  r.innerHTML = `<span class="rb-k">${esc(T('partner_label'))}</span>
    <${tag} class="rb-main" ${s.url ? `href="${esc(s.url)}" target="_blank" rel="noopener" style="text-decoration:none"` : ''}><b>${esc(s.name)}</b><span>${esc(tr(s.tagline))}</span></${tag}>
    <span class="rb-dots">${list.map((_, i) => `<button aria-label="${i + 1}" aria-current="${i === S.ribbon % list.length}" data-rb="${i}"></button>`).join('')}</span>`;
}
function renderPartners() {
  $('partners').innerHTML = CFG.sponsors.filter((s) => s.name).map((s) => {
    const inner = `<span class="k">${esc(T('partner_label'))}</span><b>${esc(s.name)}</b><span>${esc(tr(s.tagline))}</span>${s.url ? `<span class="go">${esc(T('partner_cta'))}<svg><use href="#i-arrow"/></svg></span>` : `<span class="go muted">${esc(T('soon'))}</span>`}`;
    return s.url ? `<a class="partner" style="--accent:${esc(s.accent)}" href="${esc(s.url)}" target="_blank" rel="noopener">${inner}</a>` : `<div class="partner" style="--accent:${esc(s.accent)}">${inner}</div>`;
  }).join('');
}
function renderArtists() {
  const counts = {}; Object.values(S.tracks).forEach((t) => { counts[t.artist] = (counts[t.artist] || 0) + 1; });
  $('artists-grid').innerHTML = CFG.artists.map((a, i) => {
    const h = hue(a.name);
    return `<article class="art-card"><div class="art-mono" style="background:radial-gradient(120% 120% at 20% 10%, ${esc(a.color || `hsl(${h} 80% 60%)`)}, hsl(${(h + 60) % 360} 70% 30%))">${esc(a.name.slice(0, 2))}</div>
      <div><h3>${esc(a.name)}</h3><p class="muted">${esc(tr(a.tagline))}</p><p class="muted small">${esc(T('artist_count', counts[a.name] || 0))}</p>
      <div class="art-links"><button class="btn solid" data-artist="${i}"><svg><use href="#i-play"/></svg>${esc(T('artist_listen'))}</button>
      ${a.youtube ? `<a class="btn ghost" href="${esc(a.youtube)}" target="_blank" rel="noopener"><svg><use href="#i-yt"/></svg>YouTube</a>` : ''}
      ${a.spotify ? `<a class="btn ghost" href="${esc(a.spotify)}" target="_blank" rel="noopener"><svg><use href="#i-sp"/></svg>Spotify</a>` : ''}</div></div></article>`;
  }).join('');
}
function langOptions(sel) { return LANGS.map((l) => `<option value="${l}" ${l === sel ? 'selected' : ''}>${esc(T('lang_' + l))}</option>`).join(''); }
function renderPub() {
  const b = $('pub-body');
  if (!S.user) { b.innerHTML = `<p class="muted">${esc(T('pub_login'))}</p><div class="join-btns" style="margin-top:12px"><a class="btn solid" href="${esc(CFG.registerUrl || '#')}" data-auth="signup">${esc(T('signup'))}</a><a class="btn ghost" href="${esc(CFG.loginUrl || '#')}" data-auth="login">${esc(T('login'))}</a></div>`; return; }
  if (!S.user.canPublish) {
    b.innerHTML = S.requested ? `<p class="status"><svg><use href="#i-upload"/></svg>${esc(T('pub_requested'))}</p>`
      : `<p class="muted">${esc(T('pub_locked'))}</p><button class="btn solid" id="pub-req" style="margin-top:12px">${esc(T('pub_request'))}</button>`;
    return;
  }
  b.innerHTML = `<form class="form" id="pub-form">
    <div class="field"><label for="pf-url">${esc(T('pub_url'))}</label><input id="pf-url" type="url" required placeholder="https://www.youtube.com/watch?v=…"></div>
    <div class="two"><div class="field"><label for="pf-title">${esc(T('pub_tit'))}</label><input id="pf-title" type="text" required maxlength="140"></div>
    <div class="field"><label for="pf-artist">${esc(T('pub_art'))}</label><input id="pf-artist" type="text" required maxlength="80" value="${esc(S.user.name || '')}"></div></div>
    <div class="two"><div class="field"><label for="pf-lang">${esc(T('pub_lang'))}</label><select id="pf-lang">${langOptions(S.lang)}</select></div>
    <div class="field"><label for="pf-type">${esc(T('pub_type'))}</label><select id="pf-type"><option value="song">${esc(T('type_song'))}</option><option value="podcast">${esc(T('type_pod'))}</option></select></div></div>
    <button class="btn solid" type="submit"><svg><use href="#i-upload"/></svg>${esc(T('pub_send'))}</button></form>`;
}
function plCover(p) { const h = hue(p.name || p.id); return `background:linear-gradient(135deg,hsl(${h} 75% 55%),hsl(${(h + 70) % 360} 70% 35%))`; }
function renderPlaylists() {
  $('tab-mine').setAttribute('aria-selected', S.tab === 'mine'); $('tab-pub').setAttribute('aria-selected', S.tab === 'pub');
  const g = $('pl-grid');
  if (S.tab === 'mine') {
    if (!S.user) { g.innerHTML = `<div class="empty">${esc(T('pl_login'))}<div class="join-btns" style="justify-content:center;margin-top:12px"><a class="btn solid" href="${esc(CFG.registerUrl || '#')}" data-auth="signup">${esc(T('signup'))}</a></div></div>`; return; }
    const cards = S.playlists.map((p) => `<article class="pl"><div class="pl-cover" style="${plCover(p)}">${esc((p.name || '?').slice(0, 2).toUpperCase())}</div>
      <div><h4>${esc(p.name)}</h4><span class="muted">${esc(T('pl_count', (p.tracks || []).length))} · ${esc(p.public ? T('pl_public_on') : T('pl_public_off'))}</span></div>
      <div class="pl-acts"><button class="mini" data-pl-play="${esc(p.id)}"><svg><use href="#i-play"/></svg>${esc(T('pl_play'))}</button>
      <button class="mini" data-pl-pub="${esc(p.id)}" aria-pressed="${!!p.public}">${esc(p.public ? T('pl_public_on') : T('pl_public_off'))}</button>
      <button class="mini" data-pl-del="${esc(p.id)}">${esc(T('pl_delete'))}</button></div></article>`).join('');
    g.innerHTML = cards + `<button class="pl new" id="pl-new"><svg style="width:28px;height:28px"><use href="#i-plus"/></svg><b>${esc(T('pl_new'))}</b>${S.playlists.length ? '' : `<span class="muted small">${esc(T('pl_empty_mine'))}</span>`}</button>`;
  } else {
    g.innerHTML = S.pubPlaylists.length ? S.pubPlaylists.map((p) => `<article class="pl"><div class="pl-cover" style="${plCover(p)}">${esc((p.name || '?').slice(0, 2).toUpperCase())}</div>
      <div><h4>${esc(p.name)}</h4><span class="muted">${esc(T('pl_count', (p.tracks || []).length))} · ${esc(T('pl_by', p.owner || ''))}</span></div>
      <div class="pl-acts"><button class="mini" data-pl-play="${esc(p.id)}" data-pub="1"><svg><use href="#i-play"/></svg>${esc(T('pl_play'))}</button></div></article>`).join('')
      : `<div class="empty">${esc(T('pl_empty_pub'))}</div>`;
  }
}
function curTrack() { if (S.mode === 'queue') return S.queue[S.qi]; const loc = locate(S.lang, vnow()); return loc.it.ref; }
function kindLabel(kind, ref) { return kind === 'hit' ? T('kind_hit') + ' #' + rankOf(ref) : T('kind_' + kind); }
function renderNow(it) {
  const r = it.ref, kind = it.kind;
  $('title').textContent = r.title;
  const art = CFG.artists.findIndex((a) => a.name === r.artist);
  $('artist').innerHTML = r.special ? esc(r.artist) : (art >= 0 ? `<button data-artist="${art}">${esc(r.artist)}</button>` : esc(r.artist));
  const ck = $('chip-kind'); ck.className = 'chip k-' + kind; ck.textContent = kindLabel(kind, r);
  $('chip-mode').textContent = S.mode === 'live' ? T('mode_live') : T('mode_queue');
  $('chip-lang').textContent = r.special ? STATIONS[S.lang].code : STATIONS[langOf(r)].code;
  $('t-du').textContent = fmt(r.dur);
  $('lnk-yt').href = r.yt ? 'https://www.youtube.com/watch?v=' + r.yt : 'https://www.youtube.com/results?search_query=' + encodeURIComponent((r.artist || 'OULBIG') + ' ' + r.title);
  const a = CFG.artists.find((x) => x.name === r.artist);
  $('lnk-sp').href = r.sp || (a && a.spotify) || CFG.socials.spotify;
  const h = hue(r.title + S.lang);
  $('art').style.background = `radial-gradient(120% 90% at 20% 15%, hsl(${h} 85% 58%), transparent 60%), radial-gradient(90% 90% at 85% 90%, hsl(${(h + 50) % 360} 90% 52%), transparent 60%), linear-gradient(135deg, hsl(${(h + 250) % 360} 55% 16%), #0B0916)`;
  $('art-glyph').textContent = kind === 'hit' ? '#' + rankOf(r) : [...String(r.title).replace(/^(EP\s?\d+|الحلقة \d+)\s*[·\-—]\s*/i, '')][0] || 'O';
  const special = !!r.special;
  ['act-like', 'act-com', 'act-pl', 'act-flag'].forEach((id) => { $(id).disabled = special; });
  renderActs(r);
  $('back-live').hidden = S.mode === 'live';
}
function renderActs(r) {
  if (!r || r.special) { $('like-n').textContent = '–'; $('com-n').textContent = '–'; $('act-like').setAttribute('aria-pressed', 'false'); return; }
  const liked = S.mine.has(r.id);
  $('act-like').setAttribute('aria-pressed', liked); $('act-like').querySelector('use').setAttribute('href', liked ? '#i-heart-f' : '#i-heart');
  $('like-n').textContent = S.likes[r.id] || 0; $('com-n').textContent = S.coms[r.id] || 0;
}
function renderLists(loc) {
  if (S.mode === 'live') {
    const c = loc.c;
    $('strip').innerHTML = c.items.map((x, i) => `<i class="s-${x.kind === 'pod' ? 'pod' : x.kind} ${i < loc.idx ? 'past' : ''}" style="flex:${x.ref.dur}" title="${esc(x.ref.title)}"></i>`).join('') + '<i class="cursor" id="cursor"></i>';
    const base = vnow();
    $('next').innerHTML = upcoming(loc, 7).map(({ it, wait }) => `<li class="row"><span class="time">${hhmm(base + wait * 1000)}</span><span class="t"><b>${esc(it.ref.title)}</b><small>${esc(it.ref.artist)} · ${fmt(it.ref.dur)}</small></span><span class="chip k-${it.kind}">${esc(kindLabel(it.kind, it.ref))}</span></li>`).join('');
  } else {
    $('strip').innerHTML = S.queue.map((x, i) => `<i class="s-song ${i < S.qi ? 'past' : ''}" style="flex:${x.dur}"></i>`).join('') + '<i class="cursor" id="cursor"></i>';
    let wait = (S.queue[S.qi] ? S.queue[S.qi].dur : 0) - qOffset(); const base = Date.now();
    $('next').innerHTML = S.queue.slice(S.qi + 1, S.qi + 8).map((t) => { const li = `<li class="row"><span class="time">${hhmm(base + wait * 1000)}</span><span class="t"><b>${esc(t.title)}</b><small>${esc(t.artist)} · ${fmt(t.dur)}</small></span><span class="chip">${esc(T(t.type === 'podcast' ? 'kind_pod' : 'kind_song'))}</span></li>`; wait += t.dur; return li; }).join('');
  }
  renderHits();
}
function renderHits() {
  const cur = curTrack();
  const arrow = { up: '▲', down: '▼', same: '=', new: 'NEW' };
  $('hits').innerHTML = S.db[S.lang].hits.map((t, i) => {
    const liked = S.mine.has(t.id);
    return `<li class="hit ${cur === t ? 'air' : ''}"><span class="rank">${i + 1}</span>
      <button class="t" data-play-hit="${i}" style="background:none;border:0;padding:0;text-align:start;color:inherit"><b>${esc(t.title)}</b><small>${esc(t.artist)}</small></button>
      <span class="trend ${esc(t.trend)}">${arrow[t.trend] || ''}</span>
      <button class="mini" data-like="${esc(t.id)}" aria-pressed="${liked}" aria-label="${esc(T('cta_b2'))}"><svg><use href="${liked ? '#i-heart-f' : '#i-heart'}"/></svg>${S.likes[t.id] || 0}</button></li>`;
  }).join('');
}
function showSync() { $('sync').textContent = S.synced ? T('sync') + ' ' + new Date(S.synced).toLocaleDateString(STATIONS[S.lang].html, { day: 'numeric', month: 'short', year: 'numeric' }) : ''; }
function renderAll() { applyLang(); renderDial(); renderSocials(); renderAccount(); renderRibbon(); renderPartners(); renderArtists(); renderPub(); renderPlaylists(); S.shownKey = ''; tick(); }

/* =====================================================================
   8. Lecture (API YouTube IFrame sur le site ; simulée dans l'aperçu)
   ===================================================================== */
function setPlaying(p) {
  S.playing = p; $('player').classList.toggle('playing', p);
  $('play-ic').setAttribute('href', p ? '#i-pause' : '#i-play');
  $('play-lbl').textContent = p ? T('pause') : (S.mode === 'live' ? T('listen') : T('pl_play'));
}
function load(ref, offset) {
  $('notice').hidden = !(S.ytOK && !ref.yt); $('notice').textContent = T('noid');
  if (S.ytOK && S.player && ref.yt) { S.player.loadVideoById({ videoId: ref.yt, startSeconds: Math.floor(offset || 0) }); S.player.setVolume(+$('vol').value); $('art').classList.add('off'); }
  else { if (S.player && S.ytOK) S.player.stopVideo(); $('art').classList.remove('off'); }
}
function playLive(loc) { S.playKey = loc.k + ':' + loc.idx; load(loc.it.ref, loc.off); maybePromo(loc.it, loc.idx + loc.k); }
function qOffset() { if (S.ytOK && S.player && S.playing && S.queue[S.qi] && S.queue[S.qi].yt) { try { return S.player.getCurrentTime() || 0; } catch (e) { return 0; } } return S.qOff + (S.playing ? (Date.now() - S.qT0) / 1000 : 0); }
function startQueue(list, i) {
  list = (list || []).filter(Boolean); if (!list.length) return;
  S.mode = 'queue'; S.queue = list; S.qi = i || 0; S.qOff = 0; S.qT0 = Date.now(); S.shownKey = '';
  setPlaying(true); load(S.queue[S.qi], 0); tick();
  document.getElementById('direct').scrollIntoView({ behavior: 'smooth' });
}
function nextInQueue() { if (S.qi < S.queue.length - 1) { S.qi++; S.qOff = 0; S.qT0 = Date.now(); S.shownKey = ''; load(S.queue[S.qi], 0); maybePromo({ kind: 'song', ref: S.queue[S.qi] }, S.qi); } else backLive(); }
function backLive() { S.mode = 'live'; S.queue = []; S.skew = 0; S.shownKey = ''; S.playKey = ''; if (S.playing) playLive(locate(S.lang, vnow())); tick(); applyLang(); }
function maybePromo(it, n) {
  const list = CFG.sponsors.filter((s) => s.name); const every = Math.max(2, +CFG.promoEvery || 3);
  if (!list.length || it.kind === 'ad' || it.kind === 'jingle' || n % every !== 0) { $('promo').hidden = true; return; }
  const s = list[Math.floor(n / every) % list.length];
  $('promo-n').textContent = s.name; $('promo-t').textContent = tr(s.tagline);
  const p = $('promo'); if (s.url) p.href = s.url; else p.removeAttribute('href');
  p.hidden = false; S.promoUntil = Date.now() + 12000;
}
$('play').onclick = () => {
  if (S.playing) { setPlaying(false); if (S.mode === 'queue') S.qOff = qOffset(); if (S.ytOK && S.player) S.player.pauseVideo(); $('art').classList.remove('off'); return; }
  setPlaying(true);
  if (S.mode === 'live') { S.skew = 0; playLive(locate(S.lang, vnow())); }
  else { S.qT0 = Date.now(); load(S.queue[S.qi], S.qOff); }
};
$('vol').oninput = (e) => { if (S.ytOK && S.player) S.player.setVolume(+e.target.value); };
$('back-live').onclick = backLive;
window.onYouTubeIframeAPIReady = () => {
  S.player = new YT.Player('yt', { width: '100%', height: '100%', playerVars: { playsinline: 1, controls: 0, rel: 0, modestbranding: 1, disablekb: 1 },
    events: { onReady: () => { S.ytOK = true; }, onStateChange: (e) => {
      S.ytState = e.data;
      if (e.data === 0) { if (S.mode === 'queue') nextInQueue(); else { const loc = locate(S.lang, vnow()); S.skew += (loc.it.ref.dur - loc.off) + 0.1; } }
    } } });
};
(function loadYT() {
  const s = document.createElement('script'); s.src = 'https://www.youtube.com/iframe_api';
  document.head.appendChild(s);
  setTimeout(() => { if (!S.ytOK && CFG.mode !== 'wp') { const n = $('notice'); n.textContent = T('preview'); n.hidden = false; setTimeout(() => { n.hidden = true; }, 9000); } }, 6000);
})();

/* =====================================================================
   9. Boucle
   ===================================================================== */
function tick() {
  const now = Date.now(), dt = (now - S.lastTick) / 1000; S.lastTick = now;
  if (S.promoUntil && now > S.promoUntil) { $('promo').hidden = true; S.promoUntil = 0; }
  if (S.mode === 'queue') {
    const t = S.queue[S.qi]; if (!t) return backLive();
    let off = qOffset();
    if (S.playing && !(S.ytOK && t.yt) && off >= t.dur) { nextInQueue(); off = 0; }
    const key = 'q' + S.qi; if (key !== S.shownKey) { S.shownKey = key; renderNow({ kind: t.type === 'podcast' ? 'pod' : 'song', ref: S.queue[S.qi] }); renderLists(null); }
    $('t-el').textContent = fmt(off); $('bar').style.width = Math.min(100, off / S.queue[S.qi].dur * 100) + '%';
    const total = S.queue.reduce((a, b) => a + b.dur, 0), before = S.queue.slice(0, S.qi).reduce((a, b) => a + b.dur, 0);
    placeCursor((before + off) / total * 100);
  } else {
    let loc = locate(S.lang, vnow()), key = loc.k + ':' + loc.idx;
    if (S.playing && S.ytOK && S.playKey && key !== S.playKey && S.ytState === 1) { S.skew -= dt; loc = locate(S.lang, vnow()); key = loc.k + ':' + loc.idx; }
    if (key !== S.shownKey) { S.shownKey = key; renderNow(loc.it); renderLists(loc); if (S.playing && key !== S.playKey) playLive(loc); }
    $('t-el').textContent = fmt(loc.off); $('bar').style.width = Math.min(100, loc.off / loc.it.ref.dur * 100) + '%';
    placeCursor(loc.cyc / loc.c.dur * 100);
  }
  $('clock').textContent = hhmm(Date.now());
}
function placeCursor(p) { const c = $('cursor'); if (!c) return; if (STATIONS[S.lang].dir === 'rtl') { c.style.right = p + '%'; c.style.left = 'auto'; } else { c.style.left = p + '%'; c.style.right = 'auto'; } }

/* =====================================================================
   10. Comptes, likes, commentaires, signalements, playlists, publication
   ===================================================================== */
function toast(msg) { const t = $('toast'); t.textContent = msg; t.hidden = false; clearTimeout(toast.h); toast.h = setTimeout(() => { t.hidden = true; }, 3200); }
function openModal(title, html) { $('m-title').textContent = title; $('m-body').innerHTML = html; $('modal').hidden = false; const f = $('m-body').querySelector('input,button,select'); if (f) f.focus(); }
function closeModal() { $('modal').hidden = true; }
$('m-close').onclick = closeModal; $('modal').addEventListener('click', (e) => { if (e.target === $('modal')) closeModal(); });
function needLogin(then) {
  if (S.user) return true;
  if (!API.real) {
    openModal(T('demo_title'), `<p class="note-demo">${esc(T('demo_sub'))}</p><form class="form" id="demo-form"><div class="field"><label for="demo-name">${esc(T('demo_name'))}</label><input id="demo-name" type="text" required maxlength="40"></div><button class="btn solid" type="submit">${esc(T('demo_go'))}</button></form>`);
    $('demo-form').onsubmit = (e) => { e.preventDefault(); S.user = API.demoLogin($('demo-name').value.trim() || 'Auditeur'); closeModal(); refreshUserData().then(() => { renderAll(); if (then) then(); }); };
    return false;
  }
  openModal(T('need_login_title'), `<p class="muted">${esc(T('need_login_sub'))}</p><div class="join-btns"><a class="btn solid" href="${esc(CFG.registerUrl)}">${esc(T('signup'))}</a><a class="btn ghost" href="${esc(CFG.loginUrl)}">${esc(T('login'))}</a></div>`);
  return false;
}
function fail(e) { toast((e && e.message) || T('err')); }
function toggleLike(id) {
  if (!needLogin(() => toggleLike(id))) return;
  API.like(id).then((r) => { r.liked ? S.mine.add(id) : S.mine.delete(id); S.likes[id] = r.count; renderActs(curTrack()); renderHits(); if (S.drawer && S.drawer.id === id) drawerActs(); }).catch(fail);
}
function addToPlaylist(t) {
  if (!t || t.special || !needLogin(() => addToPlaylist(t))) return;
  const opts = S.playlists.map((p) => `<button class="opt" data-add-to="${esc(p.id)}"><span>${esc(p.name)}</span><span class="muted small">${esc(T('pl_count', (p.tracks || []).length))}</span></button>`).join('');
  openModal(T('pl_choose'), `<div class="opt-list">${opts}</div><form class="form" id="pl-quick"><div class="field"><label for="pl-qname">${esc(T('pl_new'))}</label><input id="pl-qname" type="text" maxlength="60" placeholder="${esc(T('pl_name'))}" required></div><button class="btn solid" type="submit"><svg><use href="#i-plus"/></svg>${esc(T('pl_create'))}</button></form>`);
  $('m-body').querySelectorAll('[data-add-to]').forEach((b) => { b.onclick = () => { const p = S.playlists.find((x) => x.id === b.dataset.addTo); if (!p) return; p.tracks = [...new Set([...(p.tracks || []), t.id])]; API.savePlaylist(p).then(() => { closeModal(); toast(T('pl_added', p.name)); renderPlaylists(); }).catch(fail); }; });
  $('pl-quick').onsubmit = (e) => { e.preventDefault(); const p = { name: $('pl-qname').value.trim(), public: false, tracks: [t.id] }; API.savePlaylist(p).then((saved) => { S.playlists.push(saved); closeModal(); toast(T('pl_added', saved.name)); renderPlaylists(); }).catch(fail); };
}
function reportLang(t) {
  if (!t || t.special || !needLogin(() => reportLang(t))) return;
  const cur = langOf(t);
  openModal(T('rep_title'), `<p class="muted">${esc(T('rep_sub'))}</p><div class="opt-list">${LANGS.map((l) => `<button class="opt" data-rep="${l}" aria-pressed="${l === cur}"><span>${esc(T('lang_' + l))}</span><span class="muted">${STATIONS[l].code}</span></button>`).join('')}</div>`);
  $('m-body').querySelectorAll('[data-rep]').forEach((b) => { b.onclick = () => API.report(t.id, b.dataset.rep).then((r) => { closeModal(); toast(T('rep_done')); if (r && r.applied) { S.overrides[t.id] = r.lang || b.dataset.rep; buildDB(); S.shownKey = ''; tick(); } }).catch(fail); });
}
/* Panneau commentaires */
function openDrawer(t) {
  if (!t || t.special) return;
  S.drawer = t; $('drawer').hidden = false;
  $('dr-title').textContent = t.title; $('dr-artist').textContent = t.artist + ' · ' + T('lang_' + langOf(t));
  $('com-lang').innerHTML = langOptions(langOf(t));
  $('com-note').textContent = S.user ? '' : T('c_login');
  drawerActs(); $('coms').innerHTML = '';
  API.comments(t.id).then((list) => {
    S.coms[t.id] = list.length;
    $('coms').innerHTML = list.length ? list.map((c) => `<li class="com"><header><b>${esc(c.name)}</b><span>${esc(new Date(c.date).toLocaleDateString(STATIONS[S.lang].html))}</span></header><p>${esc(c.text)}</p>${c.lang && c.lang !== langOf(t) ? `<span class="tag">${esc(T('c_lang_q'))} : ${esc(T('lang_' + c.lang))}</span>` : ''}</li>`).join('') : `<li class="empty">${esc(T('c_empty'))}</li>`;
    renderActs(curTrack());
  }).catch(fail);
  $('dr-close').focus();
}
function drawerActs() { const t = S.drawer; if (!t) return; const liked = S.mine.has(t.id); $('dr-like').setAttribute('aria-pressed', liked); $('dr-like').querySelector('use').setAttribute('href', liked ? '#i-heart-f' : '#i-heart'); $('dr-like-n').textContent = S.likes[t.id] || 0; }
$('dr-close').onclick = () => { $('drawer').hidden = true; S.drawer = null; };
$('drawer').addEventListener('click', (e) => { if (e.target === $('drawer')) { $('drawer').hidden = true; S.drawer = null; } });
$('dr-like').onclick = () => S.drawer && toggleLike(S.drawer.id);
$('dr-pl').onclick = () => S.drawer && addToPlaylist(S.drawer);
$('com-form').onsubmit = (e) => {
  e.preventDefault(); const t = S.drawer; if (!t) return;
  if (!needLogin(() => openDrawer(t))) return;
  const text = $('com-text').value.trim(); const lang = $('com-lang').value;
  const cur = langOf(t);
  const jobs = [];
  if (text) jobs.push(API.comment(t.id, text, lang !== cur ? lang : ''));
  if (lang !== cur) jobs.push(API.report(t.id, lang).then((r) => { if (r && r.applied) { S.overrides[t.id] = r.lang || lang; buildDB(); S.shownKey = ''; } }));
  if (!jobs.length) return;
  Promise.all(jobs).then(() => { $('com-text').value = ''; toast(text ? T('c_sent') : T('rep_done')); openDrawer(t); }).catch(fail);
};
/* Données de l'utilisateur */
function refreshUserData() {
  if (!S.user) { S.playlists = []; return API.publicPlaylists().then((l) => { S.pubPlaylists = l || []; }).catch(() => {}); }
  return Promise.all([API.playlists().then((l) => { S.playlists = l || []; }), API.publicPlaylists().then((l) => { S.pubPlaylists = l || []; })]).catch(() => {});
}
function playlistTracks(p) { return (p.tracks || []).map((id) => S.tracks[id]).filter(Boolean); }

/* Délégation des clics */
document.addEventListener('click', (e) => {
  const el = e.target.closest('button, a'); if (!el) return;
  const d = el.dataset;
  if (d.l) { switchStation(d.l); return; }
  if (d.rb !== undefined) { S.ribbon = +d.rb; renderRibbon(); return; }
  if (d.like) { toggleLike(d.like); return; }
  if (d.playHit !== undefined) { startQueue(S.db[S.lang].hits, +d.playHit); return; }
  if (d.artist !== undefined) { const a = CFG.artists[+d.artist]; if (a) startQueue(Object.values(S.tracks).filter((t) => t.artist === a.name && t.type === 'song').sort((x, y) => (y.views || 0) - (x.views || 0)), 0); return; }
  if (d.plPlay) { const src = d.pub ? S.pubPlaylists : S.playlists; const p = src.find((x) => String(x.id) === d.plPlay); if (p) startQueue(playlistTracks(p), 0); return; }
  if (d.plDel) { API.deletePlaylist(d.plDel).then(() => { S.playlists = S.playlists.filter((p) => String(p.id) !== d.plDel); renderPlaylists(); }).catch(fail); return; }
  if (d.plPub) { const p = S.playlists.find((x) => String(x.id) === d.plPub); if (p) { p.public = !p.public; API.savePlaylist(p).then(() => { refreshUserData().then(renderPlaylists); }).catch(fail); } return; }
  if (d.auth && !API.real) { e.preventDefault(); needLogin(); return; }
  if (el.id === 'pl-new') { if (!needLogin()) return; openModal(T('pl_new'), `<form class="form" id="pl-create"><div class="field"><label for="pl-name">${esc(T('pl_name'))}</label><input id="pl-name" type="text" maxlength="60" required></div><button class="btn solid" type="submit">${esc(T('pl_create'))}</button></form>`); $('pl-create').onsubmit = (ev) => { ev.preventDefault(); API.savePlaylist({ name: $('pl-name').value.trim(), public: false, tracks: [] }).then((p) => { S.playlists.push(p); closeModal(); renderPlaylists(); }).catch(fail); }; return; }
  if (el.id === 'pub-req') { if (!needLogin()) return; API.requestAccess().then(() => { S.requested = true; renderPub(); }).catch(fail); return; }
  if (el.id === 'acc-btn') {
    openModal(S.user.name, `<div class="opt-list"><a class="opt" href="#playlists" id="acc-pl"><span>${esc(T('pl_mine'))}</span><svg><use href="#i-arrow"/></svg></a>${API.real ? `<a class="opt" href="${esc(CFG.logoutUrl)}"><span>${esc(T('logout'))}</span></a>` : `<button class="opt" id="acc-out"><span>${esc(T('logout'))}</span></button>`}</div>${API.real ? '' : `<p class="note-demo">${esc(T('demo_sub'))}</p>`}`);
    $('acc-pl').onclick = closeModal;
    const out = $('acc-out'); if (out) out.onclick = () => { API.demoLogout(); S.user = null; S.mine = new Set(); closeModal(); refreshUserData().then(renderAll); };
  }
});
$('act-like').onclick = () => { const t = curTrack(); if (t && !t.special) toggleLike(t.id); };
$('act-com').onclick = () => openDrawer(curTrack());
$('act-pl').onclick = () => addToPlaylist(curTrack());
$('act-flag').onclick = () => reportLang(curTrack());
$('tab-mine').onclick = () => { S.tab = 'mine'; renderPlaylists(); };
$('tab-pub').onclick = () => { S.tab = 'pub'; renderPlaylists(); };
document.addEventListener('submit', (e) => {
  if (e.target.id !== 'pub-form') return; e.preventDefault();
  const url = $('pf-url').value.trim(); if (!ytId(url)) { toast(T('pub_bad_url')); return; }
  API.submit({ url, title: $('pf-title').value.trim(), artist: $('pf-artist').value.trim(), lang: $('pf-lang').value, type: $('pf-type').value })
    .then(() => { e.target.reset(); toast(T('pub_sent')); renderPub(); }).catch(fail);
});
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeModal(); $('drawer').hidden = true; S.drawer = null; } });

function switchStation(l) {
  if (!LANGS.includes(l) || l === S.lang) return;
  S.lang = l; try { localStorage.setItem('orad-st', l); } catch (e) {}
  if (S.mode === 'queue') backLive();
  S.skew = 0; S.shownKey = ''; renderAll();
  if (S.playing && S.mode === 'live') playLive(locate(S.lang, vnow()));
}

/* =====================================================================
   11. Démarrage
   ===================================================================== */
document.documentElement.dataset.skin = CFG.skin || 'auto';
S.tracks = sampleTracks(); S.sample = true; buildDB(); renderAll();
setInterval(tick, 500);
setInterval(() => { S.ribbon++; renderRibbon(); }, 8000);

const boot = API.bootstrap().then((b) => {
  b = b || {};
  if (b.user !== undefined) S.user = b.user;
  if (b.user && b.user.requested) S.requested = true;
  S.likes = b.likes || {}; S.mine = new Set(b.mine || []); S.coms = b.comments || {};
  S.overrides = b.overrides || {}; S.extra = b.extra || []; S.hitLikes = b.hitLikes || b.likes || {};
}).catch(() => {});
const feed = fetch(CFG.catalogueUrl, { cache: 'no-cache' }).then((r) => r.json()).catch(() => null);
Promise.all([boot, feed]).then(([, f]) => {
  if (f && f.items && f.items.length) { const t = ingest(f); if (Object.keys(t).length) { S.tracks = t; S.sample = false; } S.synced = f.generated || ''; }
  addExtra(S.extra); buildDB();
  return refreshUserData();
}).then(() => { renderAll(); if (S.playing && S.mode === 'live') playLive(locate(S.lang, vnow())); });

/* Application installable (site WordPress / GitHub Pages) */
let deferred = null;
window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); deferred = e; });
try { if ('serviceWorker' in navigator && CFG.sw) navigator.serviceWorker.register(CFG.sw).catch(() => {}); } catch (e) {}
})();
