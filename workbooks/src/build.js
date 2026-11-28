// Génère les cahiers PDF « One Habit a Day » et « Old Remedies Handbook »
// en 6 langues. Usage : node workbooks/src/build.js
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const SRC = __dirname;
const OUT = path.join(SRC, '..', 'pdf');
const LANGS = process.env.LANGS ? process.env.LANGS.split(',') : ['fr', 'en', 'es', 'ar', 'darija', 'de'];

const fontUrl = (f) => 'file://' + path.join(SRC, 'fonts', f);
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

function css(theme, rtl) {
  const head = rtl ? "'Amiri', serif" : "'Playfair Display', serif";
  const body = rtl ? "'Noto Naskh Arabic', serif" : "'Lora', serif";
  return `
@font-face{font-family:'Amiri';font-weight:400;src:url(${fontUrl('amiri-arabic-400-normal.woff2')})}
@font-face{font-family:'Amiri';font-weight:700;src:url(${fontUrl('amiri-arabic-700-normal.woff2')})}
@font-face{font-family:'Noto Naskh Arabic';font-weight:400;src:url(${fontUrl('noto-naskh-arabic-arabic-400-normal.woff2')})}
@font-face{font-family:'Noto Naskh Arabic';font-weight:700;src:url(${fontUrl('noto-naskh-arabic-arabic-700-normal.woff2')})}
@font-face{font-family:'Playfair Display';font-weight:400;src:url(${fontUrl('playfair-display-latin-400-normal.woff2')})}
@font-face{font-family:'Playfair Display';font-weight:700;src:url(${fontUrl('playfair-display-latin-700-normal.woff2')})}
@font-face{font-family:'Lora';font-weight:400;src:url(${fontUrl('lora-latin-400-normal.woff2')})}
@font-face{font-family:'Lora';font-weight:700;src:url(${fontUrl('lora-latin-700-normal.woff2')})}
@font-face{font-family:'Lora';font-style:italic;src:url(${fontUrl('lora-latin-400-italic.woff2')})}
@page{size:A4;margin:0}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{font-family:${body};color:#2b2a27;font-size:${rtl ? '12.5pt' : '11pt'};line-height:${rtl ? 1.75 : 1.5};-webkit-print-color-adjust:exact;print-color-adjust:exact}
h1,h2,h3{font-family:${head};font-weight:700;margin:0;color:${theme.dark}}
.page{width:210mm;height:297mm;padding:18mm 18mm 16mm;position:relative;page-break-after:always;overflow:hidden;background:${theme.paper}}
.page:last-child{page-break-after:auto}
.foot{position:absolute;bottom:8mm;left:18mm;right:18mm;display:flex;justify-content:space-between;font-size:8.5pt;color:${theme.muted};border-top:1px solid ${theme.line};padding-top:2mm}
.kicker{font-size:9pt;letter-spacing:.18em;text-transform:uppercase;color:${theme.accent};font-weight:700;margin-bottom:2mm}
h2{font-size:${rtl ? '24pt' : '22pt'};margin-bottom:5mm}
h3{font-size:${rtl ? '15pt' : '13.5pt'};margin:4mm 0 1.5mm}
p{margin:0 0 3mm}
.lead{font-size:${rtl ? '13.5pt' : '12pt'};color:#4a4741}
.line{border-bottom:1px solid ${theme.line};height:${rtl ? '10mm' : '8.5mm'}}
.field{margin-bottom:4mm}
.field label{display:block;font-weight:700;color:${theme.dark};font-size:${rtl ? '11.5pt' : '10pt'}}
.box{border:1.2px solid ${theme.line};border-radius:3mm;padding:4mm 5mm;margin-bottom:4mm;background:#fff}
.tint{background:${theme.tint};border-color:${theme.tint}}
ol.steps{padding:0;margin:0;list-style:none;counter-reset:s}
ol.steps li{counter-increment:s;position:relative;padding-${rtl ? 'right' : 'left'}:13mm;margin-bottom:5mm;min-height:9mm}
ol.steps li::before{content:counter(s);position:absolute;${rtl ? 'right' : 'left'}:0;top:0;width:9mm;height:9mm;border-radius:50%;background:${theme.accent};color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif}
ul.dots{margin:0;padding-${rtl ? 'right' : 'left'}:5mm}
ul.dots li{margin-bottom:2mm}
.circles{display:inline-flex;gap:2.5mm;vertical-align:middle}
.circles span{width:5.5mm;height:5.5mm;border:1.2px solid ${theme.accent};border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:7.5pt;color:${theme.muted};font-family:'Lora',serif}
.check{display:inline-block;width:5mm;height:5mm;border:1.4px solid ${theme.accent};border-radius:1mm;vertical-align:middle;margin-${rtl ? 'left' : 'right'}:2mm}
.row{display:flex;gap:6mm}
.row>*{flex:1}
.cover{background:${theme.dark};color:#fff;display:flex;flex-direction:column;justify-content:space-between;padding:26mm 22mm}
.cover h1{color:#fff;font-size:${rtl ? '44pt' : '46pt'};line-height:1.15;margin:6mm 0}
.cover .sub{font-size:${rtl ? '16pt' : '14pt'};color:${theme.coverSub};max-width:150mm}
.cover .badge{display:inline-block;border:1px solid ${theme.coverSub};color:${theme.coverSub};border-radius:20mm;padding:1.5mm 5mm;font-size:10pt;letter-spacing:.12em;text-transform:uppercase}
.cover .brand{font-family:'Playfair Display',serif;letter-spacing:.35em;font-size:12pt;color:${theme.coverSub}}
.cover .art{font-size:70pt;line-height:1;color:${theme.accentLight}}
table{width:100%;border-collapse:collapse}
td,th{border:1px solid ${theme.line};padding:2mm;text-align:${rtl ? 'right' : 'left'};font-size:${rtl ? '10.5pt' : '9.5pt'}}
th{background:${theme.tint};color:${theme.dark}}
`;
}

function page(inner, foot, n, extra = '') {
  return `<section class="page ${extra}">${inner}<div class="foot"><span>${esc(foot)}</span><span>${n}</span></div></section>`;
}
const lines = (k) => Array.from({ length: k }, () => '<div class="line"></div>').join('');
const circles = (k) => `<span class="circles">${Array.from({ length: k }, (_, i) => `<span>${i + 1}</span>`).join('')}</span>`;

// ---------- One Habit a Day ----------
function habitBook(t) {
  const H = t.habit;
  const pages = [];
  let n = 1;
  pages.push(`<section class="page cover"><div><span class="badge">${esc(H.badge)}</span></div>
    <div><div class="art">☀</div><h1>${esc(H.title)}</h1><p class="sub">${esc(H.subtitle)}</p></div>
    <div class="brand">OULBIG</div></section>`);
  n++;
  pages.push(page(`<div class="kicker">${esc(H.badge)}</div><h2>${esc(H.howTitle)}</h2>
    <p class="lead">${esc(H.howIntro)}</p><ol class="steps">${H.howSteps.map((s) => `<li>${esc(s)}</li>`).join('')}</ol>
    <div class="box tint"><p style="margin:0">${esc(H.howNote)}</p></div>`, H.footer, n++));
  pages.push(page(`<div class="kicker">${esc(H.badge)}</div><h2>${esc(H.commitTitle)}</h2>
    <div class="row"><div class="field"><label>${esc(H.fName)}</label>${lines(1)}</div><div class="field"><label>${esc(H.fStart)}</label>${lines(1)}</div></div>
    <div class="field"><label>${esc(H.fWhy)}</label>${lines(5)}</div>
    <div class="field"><label>${esc(H.fFeel)}</label>${lines(4)}</div>
    <div class="field"><label>${esc(H.fObstacle)}</label>${lines(3)}</div>
    <div class="box tint"><p style="margin:0 0 6mm">${esc(H.promise)}</p><div class="row"><div class="field"><label>${esc(H.fSign)}</label>${lines(1)}</div><div class="field"><label>${esc(H.fDate)}</label>${lines(1)}</div></div></div>`, H.footer, n++));

  const day = (d) => {
    const h = H.habits[d - 1];
    return `<div class="box" style="height:118mm;display:flex;flex-direction:column">
      <div style="display:flex;justify-content:space-between;align-items:baseline"><h3 style="margin:0">${esc(H.day)} ${d}</h3><span>${esc(H.date)} ____ / ____</span></div>
      <div class="kicker" style="margin:3mm 0 1mm">${esc(H.today)}</div>
      <div style="font-family:inherit;font-weight:700;font-size:1.15em;color:inherit">${esc(h[0])}</div>
      <p style="color:#5d5a52;font-style:${t.rtl ? 'normal' : 'italic'}">${esc(h[1])}</p>
      <div style="display:flex;gap:8mm;margin-bottom:2mm"><span><span class="check"></span>${esc(H.done)}</span><span>${esc(H.mood)} ${circles(5)}</span></div>
      <div class="field" style="margin:0"><label>${esc(H.noticed)}</label>${lines(3)}</div>
      <div class="field" style="margin:0"><label>${esc(H.tomorrow)}</label>${lines(1)}</div></div>`;
  };
  const review = (w) => `<div class="box tint" style="height:118mm">
      <h3 style="margin-top:0">${esc(H.weekReview.replace('{n}', w))}</h3>
      <div class="field"><label>${esc(H.wins)}</label>${lines(2)}</div>
      <div class="field"><label>${esc(H.hard)}</label>${lines(2)}</div>
      <div class="field"><label>${esc(H.keep)}</label>${lines(1)}</div>
      <div><b>${esc(H.score)}</b> ${circles(10)}</div></div>`;
  for (let w = 1; w <= 4; w++) {
    const s = (w - 1) * 7 + 1;
    pages.push(page(day(s) + day(s + 1), H.footer, n++));
    pages.push(page(day(s + 2) + day(s + 3), H.footer, n++));
    pages.push(page(day(s + 4) + day(s + 5), H.footer, n++));
    pages.push(page(day(s + 6) + review(w), H.footer, n++));
  }
  pages.push(page(day(29) + day(30), H.footer, n++));

  // tracker
  const grid = Array.from({ length: 30 }, (_, i) => `<td style="height:24mm;vertical-align:top;width:16.6%"><b>${i + 1}</b><div style="font-size:8pt;color:#777;margin-top:1mm">${esc(H.habits[i][0])}</div></td>`);
  let rows = '';
  for (let r = 0; r < 5; r++) rows += `<tr>${grid.slice(r * 6, r * 6 + 6).join('')}</tr>`;
  pages.push(page(`<div class="kicker">${esc(H.badge)}</div><h2>${esc(H.trackerTitle)}</h2><p class="lead">${esc(H.trackerNote)}</p><table>${rows}</table>`, H.footer, n++));

  pages.push(page(`<div class="kicker">${esc(H.badge)}</div><h2>${esc(H.finalTitle)}</h2>
    ${H.finalQs.map((q) => `<div class="field"><label>${esc(q)}</label>${lines(4)}</div>`).join('')}`, H.footer, n++));

  pages.push(page(`<div style="border:3px double ${'#7a8f6a'};height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20mm">
      <div class="art" style="font-size:48pt">☀</div><h2 style="font-size:30pt">${esc(H.certTitle)}</h2>
      <p class="lead" style="max-width:130mm">${esc(H.certText)}</p>
      <div style="width:110mm;margin-top:14mm" class="row"><div class="field"><label>${esc(H.fSign)}</label>${lines(1)}</div><div class="field"><label>${esc(H.fDate)}</label>${lines(1)}</div></div>
      <div class="brand" style="margin-top:12mm;letter-spacing:.35em">OULBIG</div></div>`, H.footer, n++));
  return pages.join('');
}

// ---------- Old Remedies Handbook ----------
function remedyBook(t) {
  const R = t.remedy;
  const pages = [];
  let n = 1;
  pages.push(`<section class="page cover"><div><span class="badge">${esc(R.badge)}</span></div>
    <div><div class="art">❦</div><h1>${esc(R.title)}</h1><p class="sub">${esc(R.subtitle)}</p></div>
    <div class="brand">OULBIG</div></section>`);
  n++;
  pages.push(page(`<div class="kicker">${esc(R.badge)}</div><h2>${esc(R.noticeTitle)}</h2>
    <div class="box tint"><ul class="dots">${R.notice.map((s) => `<li>${esc(s)}</li>`).join('')}</ul></div>
    <h3>${esc(R.howTitle)}</h3><ol class="steps">${R.howSteps.map((s) => `<li>${esc(s)}</li>`).join('')}</ol>`, R.footer, n++));
  const pantry = R.pantry.map((p) => `<div style="padding:2.2mm 0;border-bottom:1px dotted #c9bfae"><span class="check"></span>${esc(p)}</div>`).join('');
  pages.push(page(`<div class="kicker">${esc(R.badge)}</div><h2>${esc(R.pantryTitle)}</h2><p class="lead">${esc(R.pantryNote)}</p>
    <div style="columns:2;column-gap:10mm">${pantry}</div>
    <h3 style="margin-top:8mm">${esc(R.contentsTitle)}</h3>
    <div style="columns:2;column-gap:10mm">${R.remedies.map((r, i) => `<div style="padding:1mm 0">${i + 1}. ${esc(r.name)}</div>`).join('')}</div>`, R.footer, n++));

  R.remedies.forEach((r, i) => {
    pages.push(page(`<div class="kicker">${esc(R.remedyLabel)} ${i + 1}</div><h2 style="margin-bottom:3mm">${esc(r.name)}</h2>
      <p class="lead"><b>${esc(R.lUse)}${t.colon}</b> ${esc(r.use)}</p>
      <div class="row"><div class="box"><h3 style="margin-top:0">${esc(R.lIngr)}</h3><ul class="dots">${r.ingr.map((x) => `<li>${esc(x)}</li>`).join('')}</ul></div>
      <div class="box"><h3 style="margin-top:0">${esc(R.lPrep)}</h3><p style="margin:0">${esc(r.prep)}</p></div></div>
      <div class="box"><h3 style="margin-top:0">${esc(R.lHow)}</h3><p style="margin:0">${esc(r.how)}</p></div>
      <div class="box tint"><b>⚠ ${esc(R.lCaution)}${t.colon}</b> ${esc(r.caution)}</div>
      <div class="row"><div class="field"><label>${esc(R.lDate)}</label>${lines(1)}</div><div class="field"><label>${esc(R.lHelped)}</label><div style="padding-top:2mm">${circles(5)}</div></div></div>
      <div class="field"><label>${esc(R.lNotes)}</label>${lines(9)}</div>`, R.footer, n++));
  });

  for (let k = 0; k < 3; k++) {
    pages.push(page(`<div class="kicker">${esc(R.badge)}</div><h2>${esc(R.familyTitle)}</h2>
      <div class="row"><div class="field"><label>${esc(R.fName)}</label>${lines(1)}</div><div class="field"><label>${esc(R.fFrom)}</label>${lines(1)}</div></div>
      <div class="field"><label>${esc(R.lUse)}</label>${lines(1)}</div>
      <div class="field"><label>${esc(R.lIngr)}</label>${lines(4)}</div>
      <div class="field"><label>${esc(R.lPrep)}</label>${lines(5)}</div>
      <div class="field"><label>${esc(R.fStory)}</label>${lines(5)}</div>`, R.footer, n++));
  }
  const logRows = Array.from({ length: 16 }, () => `<tr>${R.logCols.map(() => '<td style="height:11mm"></td>').join('')}</tr>`).join('');
  pages.push(page(`<div class="kicker">${esc(R.badge)}</div><h2>${esc(R.logTitle)}</h2><p class="lead">${esc(R.logNote)}</p>
    <table><tr>${R.logCols.map((c) => `<th>${esc(c)}</th>`).join('')}</tr>${logRows}</table>`, R.footer, n++));
  pages.push(page(`<div style="height:100%;display:flex;flex-direction:column;justify-content:center;text-align:center;padding:0 15mm">
    <div class="art" style="font-size:50pt;color:#a0643c">❦</div><h2 style="font-size:28pt">${esc(R.endTitle)}</h2>
    <p class="lead">${esc(R.endText)}</p><div class="brand" style="margin-top:12mm;letter-spacing:.35em">OULBIG</div></div>`, R.footer, n++));
  return pages.join('');
}

const THEMES = {
  habit: { dark: '#2f4a3a', accent: '#5f8a6b', accentLight: '#e8c872', paper: '#fbfaf5', tint: '#eaf1e6', line: '#c9d3c3', muted: '#7a8578', coverSub: '#dfe9d9' },
  remedy: { dark: '#4a2f22', accent: '#a0643c', accentLight: '#e7c48d', paper: '#fbf7ef', tint: '#f3e7d6', line: '#d8c9b3', muted: '#8a7a68', coverSub: '#f0e2cc' },
};

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch();
  const pageObj = await browser.newPage();
  for (const lang of LANGS) {
    const t = require(path.join(SRC, 'content', lang + '.js'));
    for (const [kind, fn, slug] of [['habit', habitBook, 'one-habit-a-day'], ['remedy', remedyBook, 'old-remedies-handbook']]) {
      const html = `<!DOCTYPE html><html lang="${t.htmlLang}" dir="${t.rtl ? 'rtl' : 'ltr'}"><head><meta charset="utf-8"><style>${css(THEMES[kind], t.rtl)}</style></head><body>${fn(t)}</body></html>`;
      const tmp = path.join(OUT, `.tmp-${slug}-${lang}.html`);
      fs.writeFileSync(tmp, html);
      await pageObj.goto('file://' + tmp, { waitUntil: 'load' });
      await pageObj.evaluate(() => document.fonts.ready);
      const file = path.join(OUT, `${slug}-${lang}.pdf`);
      await pageObj.pdf({ path: file, format: 'A4', printBackground: true, preferCSSPageSize: true });
      fs.unlinkSync(tmp);
      console.log('✓', path.basename(file));
    }
  }
  await browser.close();
})();
