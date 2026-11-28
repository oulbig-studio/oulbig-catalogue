// Génère les couvertures (PNG) : format portrait A4 et format carré pour les fiches produit.
// Usage : node workbooks/src/covers.js
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const SRC = __dirname;
const OUT = path.join(SRC, '..', 'covers');
const LANGS = process.env.LANGS ? process.env.LANGS.split(',') : ['fr', 'en', 'es', 'ar', 'darija', 'de'];
const font = (f) => 'file://' + path.join(SRC, 'fonts', f);
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');

const EXTRA = {
  fr: { pages: 'pages', pdf: 'PDF imprimable', lang: 'Français' },
  en: { pages: 'pages', pdf: 'Printable PDF', lang: 'English' },
  es: { pages: 'páginas', pdf: 'PDF imprimible', lang: 'Español' },
  de: { pages: 'Seiten', pdf: 'Druckbares PDF', lang: 'Deutsch' },
  ar: { pages: 'صفحة', pdf: 'ملف PDF قابل للطباعة', lang: 'العربية' },
  darija: { pages: 'صفحة', pdf: 'PDF واجد للطباعة', lang: 'الدارجة' },
};

const THEMES = {
  habit: { bg1: '#2f4a3a', bg2: '#1f3328', accent: '#e8c872', soft: '#dfe9d9', pages: 23 },
  remedy: { bg1: '#5a3829', bg2: '#3a2219', accent: '#e7c48d', soft: '#f0e2cc', pages: 20 },
};

// Illustrations vectorielles
const sunArt = (c) => `<svg viewBox="0 0 200 200" width="100%" height="100%"><g fill="none" stroke="${c}" stroke-width="3" stroke-linecap="round">
  ${Array.from({ length: 24 }, (_, i) => { const a = (i * 15 * Math.PI) / 180; const r1 = i % 2 ? 62 : 58; const r2 = i % 2 ? 78 : 92;
    return `<line x1="${100 + r1 * Math.cos(a)}" y1="${100 + r1 * Math.sin(a)}" x2="${100 + r2 * Math.cos(a)}" y2="${100 + r2 * Math.sin(a)}"/>`; }).join('')}
  <circle cx="100" cy="100" r="44" fill="${c}" fill-opacity=".18"/><circle cx="100" cy="100" r="44"/>
  <path d="M78 102 l14 14 l30 -32" stroke-width="7"/></g></svg>`;
const herbArt = (c) => `<svg viewBox="0 0 200 200" width="100%" height="100%"><g fill="none" stroke="${c}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
  <path d="M60 150 h80 l-8 34 h-64 z" fill="${c}" fill-opacity=".18"/><path d="M54 150 h92"/>
  <path d="M100 150 C100 110 96 80 100 30"/>
  ${[[100, 120, -1], [100, 100, 1], [99, 80, -1], [99, 60, 1], [100, 42, -1]].map(([x, y, s]) =>
    `<path d="M${x} ${y} q${s * 30} -6 ${s * 38} -26 q${-s * 28} 2 ${-s * 38} 26 z" fill="${c}" fill-opacity=".25"/>`).join('')}
  <path d="M70 150 C66 125 56 112 44 104"/><path d="M58 120 q-18 -2 -24 -16 q16 0 24 16z" fill="${c}" fill-opacity=".25"/>
  <path d="M130 150 C136 128 148 118 160 112"/><path d="M146 122 q14 -10 22 -4 q-8 10 -22 4z" fill="${c}" fill-opacity=".25"/></g></svg>`;

function html(t, kind, lang, w, h) {
  const th = THEMES[kind];
  const B = t[kind];
  const ex = EXTRA[lang];
  const rtl = t.rtl;
  const head = rtl ? "'Amiri'" : "'Playfair Display'";
  const body = rtl ? "'Noto Naskh Arabic'" : "'Lora'";
  const u = w / 100; // unité relative
  const square = w === h;
  return `<!DOCTYPE html><html lang="${t.htmlLang}" dir="${rtl ? 'rtl' : 'ltr'}"><head><meta charset="utf-8"><style>
@font-face{font-family:'Amiri';font-weight:700;src:url(${font('amiri-arabic-700-normal.woff2')})}
@font-face{font-family:'Noto Naskh Arabic';font-weight:400;src:url(${font('noto-naskh-arabic-arabic-400-normal.woff2')})}
@font-face{font-family:'Noto Naskh Arabic';font-weight:700;src:url(${font('noto-naskh-arabic-arabic-700-normal.woff2')})}
@font-face{font-family:'Playfair Display';font-weight:700;src:url(${font('playfair-display-latin-700-normal.woff2')})}
@font-face{font-family:'Playfair Display';font-weight:400;src:url(${font('playfair-display-latin-400-normal.woff2')})}
@font-face{font-family:'Lora';font-weight:400;src:url(${font('lora-latin-400-normal.woff2')})}
@font-face{font-family:'Lora';font-weight:700;src:url(${font('lora-latin-700-normal.woff2')})}
*{box-sizing:border-box;margin:0;padding:0}
body{width:${w}px;height:${h}px;overflow:hidden;font-family:${body},serif;color:#fff;
 background:radial-gradient(circle at 30% 20%, ${th.bg1} 0%, ${th.bg2} 75%)}
.frame{position:absolute;inset:${3 * u}px;border:${0.25 * u}px solid ${th.accent}55;border-radius:${1.5 * u}px}
.wrap{position:absolute;inset:${7 * u}px;display:flex;flex-direction:column;align-items:center;text-align:center;justify-content:space-between}
.badge{font-size:${(rtl ? 2.6 : 2.2) * u}px;letter-spacing:${rtl ? 0 : 0.25 * u}px;text-transform:uppercase;color:${th.accent};border:${0.2 * u}px solid ${th.accent};border-radius:${10 * u}px;padding:${0.9 * u}px ${3 * u}px}
.art{width:${(square ? 30 : 42) * u}px;height:${(square ? 30 : 42) * u}px}
h1{font-family:${head},serif;font-weight:700;font-size:${(square ? (rtl ? 8.5 : 8) : (rtl ? 11 : 10.5)) * u}px;line-height:1.12;max-width:${86 * u}px}
.sub{font-size:${(square ? 2.7 : 3.1) * u}px;color:${th.soft};max-width:${72 * u}px;line-height:1.5;margin-top:${2.5 * u}px}
.rule{width:${14 * u}px;height:${0.3 * u}px;background:${th.accent};margin:${3 * u}px auto 0}
.meta{display:flex;gap:${2 * u}px;flex-wrap:wrap;justify-content:center;font-size:${2.2 * u}px;color:${th.soft}}
.meta span{background:#ffffff14;border-radius:${10 * u}px;padding:${0.7 * u}px ${2.4 * u}px}
.brand{font-family:'Playfair Display',serif;letter-spacing:${1.2 * u}px;font-size:${2.8 * u}px;color:${th.accent};margin-top:${2.5 * u}px}
</style></head><body><div class="frame"></div><div class="wrap">
<div class="badge">${esc(B.badge)}</div>
<div class="art">${kind === 'habit' ? sunArt(th.accent) : herbArt(th.accent)}</div>
<div><h1>${esc(B.title)}</h1><div class="rule"></div><p class="sub">${esc(B.subtitle)}</p></div>
<div><div class="meta"><span>${esc(ex.pdf)}</span><span>${th.pages} ${esc(ex.pages)}</span><span>${esc(ex.lang)}</span></div><div class="brand">OULBIG</div></div>
</div></body></html>`;
}

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch();
  const formats = [['portrait', 1654, 2339], ['carre', 2000, 2000]];
  for (const lang of LANGS) {
    const t = require(path.join(SRC, 'content', lang + '.js'));
    for (const [kind, slug] of [['habit', 'one-habit-a-day'], ['remedy', 'old-remedies-handbook']]) {
      for (const [fmt, w, h] of formats) {
        const page = await browser.newPage({ viewport: { width: w, height: h } });
        const tmp = path.join(OUT, `.tmp.html`);
        fs.writeFileSync(tmp, html(t, kind, lang, w, h));
        await page.goto('file://' + tmp);
        await page.evaluate(() => document.fonts.ready);
        const file = path.join(OUT, `${slug}-${lang}-${fmt}.png`);
        await page.screenshot({ path: file });
        await page.close();
        fs.unlinkSync(tmp);
        console.log('✓', path.basename(file));
      }
    }
  }
  await browser.close();
})();
