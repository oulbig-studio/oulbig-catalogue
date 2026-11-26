/* Publitarium Events — application monopage (sans dépendance) */
(() => {
  "use strict";

  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const main = $("#main");
  const store = {
    get: (k, d) => { try { return JSON.parse(localStorage.getItem("pe_" + k)) ?? d; } catch { return d; } },
    set: (k, v) => { try { localStorage.setItem("pe_" + k, JSON.stringify(v)); } catch {} }
  };

  let DATA = { cities: [], categories: [], articles: [], events: [], places: [] };
  let city = store.get("city", "Toutes");
  let favs = new Set(store.get("favs", []));

  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const cat = (slug) => DATA.categories.find((c) => c.slug === slug) || { slug, name: slug, icon: "spark", color: "#ff4d6d" };
  const grad = (slug) => { const c = cat(slug).color; return `linear-gradient(135deg, ${c}, color-mix(in srgb, ${c} 45%, #1d1a24))`; };
  const icon = (id, cls = "") => `<svg class="${cls}" aria-hidden="true"><use href="#i-${id}"/></svg>`;
  const parseDate = (s) => (s ? new Date(s + "T12:00:00") : null);
  const fmt = (s, opt = { day: "numeric", month: "long", year: "numeric" }) => (s ? parseDate(s).toLocaleDateString("fr-FR", opt) : "");
  const today = () => { const d = new Date(); d.setHours(0, 0, 0, 0); return d; };
  const inCity = (x) => city === "Toutes" || x.city === city || x.city === "National";

  function toast(msg) {
    const t = $("#toast");
    t.textContent = msg;
    t.classList.add("show");
    clearTimeout(toast._t);
    toast._t = setTimeout(() => t.classList.remove("show"), 2200);
  }

  /* ---------- Thème ---------- */
  const setTheme = (t) => { document.documentElement.dataset.theme = t; store.set("theme", t); };
  setTheme(store.get("theme", matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"));
  $("#themeBtn").addEventListener("click", () => setTheme(document.documentElement.dataset.theme === "dark" ? "light" : "dark"));

  /* ---------- Favoris ---------- */
  function toggleFav(key) {
    favs.has(key) ? favs.delete(key) : favs.add(key);
    store.set("favs", [...favs]);
    $$(`[data-fav="${CSS.escape(key)}"]`).forEach((b) => paintFav(b, key));
    toast(favs.has(key) ? "Ajouté aux favoris" : "Retiré des favoris");
  }
  function paintFav(btn, key) {
    const on = favs.has(key);
    btn.classList.toggle("on", on);
    btn.setAttribute("aria-pressed", on);
    btn.innerHTML = icon(on ? "heart-fill" : "heart");
  }
  const favBtn = (key, label) => `<button class="fav-btn${favs.has(key) ? " on" : ""}" data-fav="${esc(key)}" aria-pressed="${favs.has(key)}" aria-label="Favori : ${esc(label)}">${icon(favs.has(key) ? "heart-fill" : "heart")}</button>`;

  /* ---------- Lecteur vocal Oulbig ---------- */
  const player = {
    el: $("#player"), audio: null, utter: null, speaking: false, paused: false, rate: 1, id: null, text: "", timer: null, start: 0, est: 0,
    voice() {
      const voices = speechSynthesis.getVoices();
      return voices.find((v) => /fr[-_]FR/i.test(v.lang) && /(google|amélie|thomas|denise|henri|natural)/i.test(v.name))
        || voices.find((v) => /^fr/i.test(v.lang)) || null;
    },
    play(article) {
      this.stop();
      this.id = article.id;
      $("#playerTitle").textContent = article.title;
      this.el.hidden = false;
      if (article.audioUrl) {
        this.audio = new Audio(article.audioUrl);
        this.audio.playbackRate = this.rate;
        this.audio.addEventListener("timeupdate", () => this.progress(this.audio.currentTime / (this.audio.duration || 1)));
        this.audio.addEventListener("ended", () => this.done());
        this.audio.play().catch(() => toast("Lecture audio impossible"));
      } else if ("speechSynthesis" in window) {
        this.text = article.voiceSummary || article.excerpt;
        this.speak();
      } else {
        toast("La lecture vocale n'est pas disponible sur ce navigateur");
        this.el.hidden = true;
        return;
      }
      this.speaking = true; this.paused = false;
      this.paint();
    },
    speak() {
      speechSynthesis.cancel();
      const u = new SpeechSynthesisUtterance(this.text);
      u.lang = "fr-FR"; u.rate = this.rate; u.pitch = 1;
      const v = this.voice(); if (v) u.voice = v;
      u.onend = () => { if (this.utter === u) this.done(); };
      this.utter = u;
      this.est = (this.text.split(/\s+/).length / (2.6 * this.rate)) * 1000;
      this.start = Date.now();
      clearInterval(this.timer);
      this.timer = setInterval(() => { if (!this.paused) this.progress((Date.now() - this.start) / this.est); }, 300);
      speechSynthesis.speak(u);
    },
    toggle() {
      if (this.audio) { this.audio.paused ? this.audio.play() : this.audio.pause(); this.paused = this.audio.paused; }
      else if (this.paused) { speechSynthesis.resume(); this.paused = false; this.start = Date.now() - this.pausedAt; }
      else { speechSynthesis.pause(); this.paused = true; this.pausedAt = Date.now() - this.start; }
      this.paint();
    },
    cycleRate() {
      this.rate = this.rate === 1 ? 1.25 : this.rate === 1.25 ? 1.5 : this.rate === 1.5 ? 0.85 : 1;
      $("#playerSpeed").textContent = this.rate + "×";
      if (this.audio) this.audio.playbackRate = this.rate;
      else if (this.speaking && !this.paused) this.speak();
    },
    progress(p) { $("#playerProgress").style.width = Math.min(100, p * 100) + "%"; },
    done() { this.progress(1); this.speaking = false; this.paused = false; clearInterval(this.timer); this.paint(); },
    stop() {
      if (this.audio) { this.audio.pause(); this.audio = null; }
      if ("speechSynthesis" in window) speechSynthesis.cancel();
      this.utter = null; clearInterval(this.timer); this.speaking = false; this.paused = false; this.progress(0); this.paint();
    },
    paint() {
      const playing = this.speaking && !this.paused;
      $("#playerToggle").innerHTML = icon(playing ? "pause" : "play");
      $("#playerToggle").setAttribute("aria-label", playing ? "Pause" : "Lecture");
      $$("[data-voice-card]").forEach((c) => {
        const mine = c.dataset.voiceCard === this.id && playing;
        c.classList.toggle("speaking", mine);
        const b = $("button", c);
        b.innerHTML = icon(mine ? "pause" : "play");
        b.setAttribute("aria-label", mine ? "Mettre en pause le résumé vocal" : "Écouter le résumé vocal");
      });
    }
  };
  if ("speechSynthesis" in window) speechSynthesis.onvoiceschanged = () => {};
  $("#playerToggle").addEventListener("click", () => {
    if (!player.speaking && player.id) { const a = DATA.articles.find((x) => x.id === player.id); if (a) return player.play(a); }
    player.toggle();
  });
  $("#playerSpeed").addEventListener("click", () => player.cycleRate());
  $("#playerClose").addEventListener("click", () => { player.stop(); player.el.hidden = true; });

  /* ---------- Composants ---------- */
  const articleCard = (a) => `
    <article class="card fade-in">
      ${favBtn("a:" + a.id, a.title)}
      <a href="#/article/${esc(a.id)}">
        <div class="visual" style="--g:${grad(a.category)}">
          <span class="tag">${icon(cat(a.category).icon)}${esc(cat(a.category).name)}</span>
          ${icon(cat(a.category).icon, "v-icon")}
        </div>
      </a>
      <div class="card-body">
        <div class="meta"><span>${esc(a.city)}</span><span class="dot">${fmt(a.date, { day: "numeric", month: "short" })}</span><span class="dot">${a.readTime} min</span></div>
        <h3><a href="#/article/${esc(a.id)}">${esc(a.title)}</a></h3>
        <p>${esc(a.excerpt)}</p>
        <span class="listen-pill">${icon("headphones")} Résumé vocal par Oulbig</span>
      </div>
    </article>`;

  const eventCard = (e) => {
    const d = parseDate(e.startDate);
    return `
    <article class="event-card fade-in">
      <div class="date-badge" style="--g:${grad(e.category)}">
        <b>${d.getDate()}</b><span>${d.toLocaleDateString("fr-FR", { month: "short" }).replace(".", "")}</span>
      </div>
      <div>
        ${favBtn("e:" + e.id, e.title)}
        <div class="meta"><span class="kicker" style="color:${cat(e.category).color}">${esc(cat(e.category).name)}</span><span>${esc(e.city)}</span></div>
        <h3><a href="#/evenement/${esc(e.id)}">${esc(e.title)}</a></h3>
        <div class="meta">${e.recurring ? `<span>${esc(e.recurring)}</span>` : `<span>${fmt(e.startDate, { day: "numeric", month: "short" })}${e.endDate ? " → " + fmt(e.endDate, { day: "numeric", month: "short", year: "numeric" }) : ""}</span>`}<span class="dot">${esc(e.venue)}</span></div>
      </div>
    </article>`;
  };

  const placeCard = (p) => `
    <article class="card fade-in">
      ${favBtn("p:" + p.id, p.name)}
      <a href="#/adresse/${esc(p.id)}">
        <div class="visual" style="--g:${grad(p.category)};height:130px">
          <span class="tag">${esc(p.type)}</span>
          ${icon(cat(p.category).icon, "v-icon")}
        </div>
      </a>
      <div class="card-body">
        <div class="meta"><span>${esc(p.city)}</span><span class="dot">${esc(p.area)}</span><span class="dot">${esc(p.price)}</span></div>
        <h3><a href="#/adresse/${esc(p.id)}">${esc(p.name)}</a></h3>
        <p>${esc(p.description)}</p>
      </div>
    </article>`;

  const sectionHead = (title, link, label = "Tout voir") => `<div class="section-head"><h2>${title}</h2>${link ? `<a href="${link}">${label} ${icon("arrow")}</a>` : ""}</div>`;
  const empty = (msg) => `<div class="empty">${msg}</div>`;

  const upcoming = (list) => list.filter((e) => inCity(e) && parseDate(e.endDate || e.startDate) >= today())
    .sort((a, b) => parseDate(a.startDate) - parseDate(b.startDate));
  const sortedArticles = () => DATA.articles.filter(inCity).sort((a, b) => b.date.localeCompare(a.date));

  /* ---------- Pages ---------- */
  function home() {
    const arts = sortedArticles();
    const featured = arts.filter((a) => a.featured);
    const top = featured[0] || arts[0];
    const side = (featured.length > 1 ? featured.slice(1, 3) : arts.slice(1, 3));
    const evs = upcoming(DATA.events);
    const fest = evs.filter((e) => e.category === "festivals");
    const rdv = evs.filter((e) => e.category === "rendez-vous");
    const places = DATA.places.filter(inCity);
    const rest = arts.filter((a) => a !== top && !side.includes(a));

    main.innerHTML = `
      ${top ? `<section class="hero fade-in">
        <a class="visual hero-main" href="#/article/${esc(top.id)}" style="--g:${grad(top.category)}">
          ${icon(cat(top.category).icon, "v-icon")}
          <div><span class="tag">${icon("spark")} À la une · ${esc(top.city)}</span></div>
          <h1>${esc(top.title)}</h1>
          <p>${esc(top.excerpt)}</p>
          <span class="btn btn-light" style="align-self:flex-start">${icon("headphones")} Écouter le résumé d'Oulbig</span>
        </a>
        <div class="hero-side">${side.map(articleCard).join("")}</div>
      </section>` : empty("Aucun article pour cette ville pour le moment.")}

      <section class="section">
        ${sectionHead("Explorer")}
        <div class="cats">${DATA.categories.map((c) => `<a class="cat" href="#/categorie/${c.slug}"><span class="ico" style="--c:${c.color};background:${c.color}">${icon(c.icon)}</span>${esc(c.name)}</a>`).join("")}</div>
      </section>

      <section class="section">
        ${sectionHead("Rendez-vous à réserver", "#/categorie/rendez-vous")}
        ${rdv.length ? `<div class="grid">${rdv.slice(0, 3).map(eventCard).join("")}</div>` : empty("Pas de rendez-vous dans cette ville pour l'instant.")}
      </section>

      <section class="section">
        ${sectionHead("Festivals à venir", "#/categorie/festivals")}
        ${fest.length ? `<div class="grid">${fest.slice(0, 6).map(eventCard).join("")}</div>` : empty("Pas de festival annoncé dans cette ville.")}
      </section>

      <section class="section">
        ${sectionHead("Bonnes adresses", "#/adresses")}
        ${places.length ? `<div class="hscroll">${places.slice(0, 8).map(placeCard).join("")}</div>` : empty("Pas encore d'adresse dans cette ville.")}
      </section>

      <section class="section">
        ${sectionHead("Dernières actus")}
        ${rest.length ? `<div class="grid">${rest.map(articleCard).join("")}</div>` : ""}
      </section>`;
  }

  function agenda(filter = "all") {
    const evs = upcoming(DATA.events).filter((e) => filter === "all" || e.category === filter);
    const groups = {};
    evs.forEach((e) => {
      const k = parseDate(e.startDate) < today() ? today() : parseDate(e.startDate);
      const key = k.toLocaleDateString("fr-FR", { month: "long", year: "numeric" });
      (groups[key] ||= []).push(e);
    });
    const used = [...new Set(DATA.events.map((e) => e.category))];
    main.innerHTML = `
      <section class="section fade-in">
        <span class="kicker">Agenda</span>
        <h1 style="font-size:34px;margin:4px 0 6px">Événements, festivals et rendez-vous</h1>
        <p class="muted">${city === "Toutes" ? "Partout au Maroc" : esc(city)} · ${evs.length} événement${evs.length > 1 ? "s" : ""} à venir</p>
        <div class="filters" role="toolbar" aria-label="Filtrer par type">
          <a class="chip${filter === "all" ? " active" : ""}" href="#/agenda">Tout</a>
          ${used.map((c) => `<a class="chip${filter === c ? " active" : ""}" href="#/agenda/${c}">${esc(cat(c).name)}</a>`).join("")}
        </div>
        ${Object.keys(groups).length ? Object.entries(groups).map(([m, list]) => `<h2 class="month-title">${m}</h2><div class="grid">${list.map(eventCard).join("")}</div>`).join("") : empty("Aucun événement à venir pour ce filtre.")}
      </section>`;
  }

  function category(slug) {
    const c = cat(slug);
    const arts = sortedArticles().filter((a) => a.category === slug);
    const evs = upcoming(DATA.events).filter((e) => e.category === slug);
    const places = DATA.places.filter((p) => inCity(p) && p.category === slug);
    main.innerHTML = `
      <section class="section fade-in">
        <div class="visual" style="--g:${grad(slug)};padding:26px 20px;min-height:150px;display:flex;flex-direction:column;justify-content:flex-end">
          ${icon(c.icon, "v-icon")}
          <span class="kicker" style="color:#fff;opacity:.85">Rubrique</span>
          <h1 style="font-size:34px">${esc(c.name)}</h1>
        </div>
        ${evs.length ? `<div class="section">${sectionHead(slug === "rendez-vous" ? "À réserver" : "Événements")}<div class="grid">${evs.map(eventCard).join("")}</div></div>` : ""}
        ${arts.length ? `<div class="section">${sectionHead("Articles")}<div class="grid">${arts.map(articleCard).join("")}</div></div>` : ""}
        ${places.length ? `<div class="section">${sectionHead("Adresses")}<div class="grid">${places.map(placeCard).join("")}</div></div>` : ""}
        ${!evs.length && !arts.length && !places.length ? `<div class="section">${empty("Rien dans cette rubrique pour cette ville pour le moment.")}</div>` : ""}
      </section>`;
  }

  function article(id) {
    const a = DATA.articles.find((x) => x.id === id);
    if (!a) return notFound();
    document.title = a.title + " · Publitarium Events";
    const related = DATA.articles.filter((x) => x.id !== a.id && (x.category === a.category || x.city === a.city)).slice(0, 3);
    main.innerHTML = `
      <article class="article fade-in">
        <a class="back" href="javascript:history.back()">${icon("back")} Retour</a>
        <div class="visual" style="--g:${grad(a.category)}">
          ${icon(cat(a.category).icon, "v-icon")}
          <div><a class="tag" href="#/categorie/${a.category}">${icon(cat(a.category).icon)}${esc(cat(a.category).name)}</a> <span class="tag">${esc(a.city)}</span></div>
        </div>
        <h1>${esc(a.title)}</h1>
        <div class="meta"><span>${esc(a.author)}</span><span class="dot">${fmt(a.date)}</span><span class="dot">${a.readTime} min de lecture</span></div>

        <div class="voice-card" data-voice-card="${esc(a.id)}">
          <div class="avatar" aria-hidden="true">O</div>
          <div>
            <small>Résumé vocal</small>
            <strong>Écoutez l'essentiel, lu par Oulbig</strong>
            <div class="wave" aria-hidden="true">${"<i></i>".repeat(24)}</div>
          </div>
          <button class="btn" data-listen="${esc(a.id)}" aria-label="Écouter le résumé vocal">${icon("play")}</button>
        </div>

        <p class="lead">${esc(a.excerpt)}</p>
        <div class="body">${a.content.map((p) => `<p>${esc(p)}</p>`).join("")}</div>
        <div class="article-actions">
          <button class="btn btn-ghost" data-share>${icon("share")} Partager</button>
          <button class="btn btn-ghost" data-fav-inline="a:${esc(a.id)}">${icon(favs.has("a:" + a.id) ? "heart-fill" : "heart")} ${favs.has("a:" + a.id) ? "Dans vos favoris" : "Ajouter aux favoris"}</button>
        </div>
        ${a.tags?.length ? `<div class="btn-row">${a.tags.map((t) => `<a class="chip" href="#/recherche/${encodeURIComponent(t)}">#${esc(t)}</a>`).join("")}</div>` : ""}
        ${related.length ? `<div class="section">${sectionHead("À lire aussi")}<div class="grid">${related.map(articleCard).join("")}</div></div>` : ""}
      </article>`;
    player.paint();
  }

  function event(id) {
    const e = DATA.events.find((x) => x.id === id);
    if (!e) return notFound();
    document.title = e.title + " · Publitarium Events";
    const bookable = e.category === "rendez-vous" || (e.slots && e.slots.length);
    main.innerHTML = `
      <article class="article fade-in">
        <a class="back" href="javascript:history.back()">${icon("back")} Retour</a>
        <div class="visual" style="--g:${grad(e.category)}">
          ${icon(cat(e.category).icon, "v-icon")}
          <div><span class="tag">${esc(cat(e.category).name)}</span> <span class="tag">${esc(e.city)}</span></div>
        </div>
        <h1>${esc(e.title)}</h1>
        <p class="lead">${esc(e.description)}</p>
        <dl class="info-list">
          <div><dt>Quand</dt><dd style="margin:0">${e.recurring ? esc(e.recurring) : fmt(e.startDate) + (e.endDate ? " → " + fmt(e.endDate) : "")}</dd></div>
          <div><dt>Où</dt><dd style="margin:0">${esc(e.venue)}</dd></div>
          ${e.price ? `<div><dt>Tarif</dt><dd style="margin:0">${esc(e.price)}</dd></div>` : ""}
        </dl>
        ${e.dateNote ? `<p class="note">${esc(e.dateNote)}</p>` : ""}
        <div class="article-actions">
          <button class="btn" data-ics="${esc(e.id)}">${icon("plus")} Ajouter au calendrier</button>
          ${e.url ? `<a class="btn btn-ghost" href="${esc(e.url)}" target="_blank" rel="noopener">Site officiel ${icon("arrow")}</a>` : ""}
          <a class="btn btn-ghost" href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(e.venue + ", " + e.city)}" target="_blank" rel="noopener">${icon("pin")} Itinéraire</a>
          <button class="btn btn-ghost" data-share>${icon("share")} Partager</button>
        </div>
        ${bookable ? bookingForm(e) : ""}
      </article>`;
  }

  function bookingForm(e) {
    const min = new Date().toISOString().slice(0, 10);
    return `
      <form class="form" data-form="rendezvous">
        <h2>Prendre rendez-vous</h2>
        <p class="muted" style="margin:0">Envoyez votre demande, nous vous recontactons pour confirmer.</p>
        <input type="hidden" name="event" value="${esc(e.title)} (${esc(e.city)})">
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <label>Nom complet<input name="name" required autocomplete="name"></label>
        <div class="row">
          <label>Téléphone<input name="phone" type="tel" required autocomplete="tel" placeholder="+212 6…"></label>
          <label>Email<input name="email" type="email" autocomplete="email"></label>
        </div>
        <div class="row">
          <label>Date<input name="date" type="date" required min="${min}"></label>
          <label>Personnes<input name="guests" type="number" min="1" max="50" value="2" required></label>
        </div>
        ${e.slots?.length ? `<fieldset style="border:0;padding:0;margin:0"><legend style="font-weight:600;font-size:14px;margin-bottom:6px">Créneau</legend><div class="slots">${e.slots.map((s, i) => `<label><input type="radio" name="slot" value="${esc(s)}"${i === 0 ? " checked" : ""}><span>${esc(s)}</span></label>`).join("")}</div></fieldset>` : ""}
        <label>Message (optionnel)<textarea name="notes" rows="3"></textarea></label>
        <button class="btn" type="submit">Envoyer ma demande</button>
        <p class="form-msg" role="status"></p>
      </form>`;
  }

  function places(filter = "all") {
    const list = DATA.places.filter((p) => inCity(p) && (filter === "all" || p.category === filter));
    const used = [...new Set(DATA.places.map((p) => p.category))];
    main.innerHTML = `
      <section class="section fade-in">
        <span class="kicker">Bonnes adresses</span>
        <h1 style="font-size:34px;margin:4px 0 6px">Où manger, sortir, flâner</h1>
        <p class="muted">${city === "Toutes" ? "Partout au Maroc" : esc(city)} · ${list.length} adresse${list.length > 1 ? "s" : ""}</p>
        <div class="filters" role="toolbar" aria-label="Filtrer par type">
          <a class="chip${filter === "all" ? " active" : ""}" href="#/adresses">Tout</a>
          ${used.map((c) => `<a class="chip${filter === c ? " active" : ""}" href="#/adresses/${c}">${esc(cat(c).name)}</a>`).join("")}
        </div>
        ${list.length ? `<div class="grid">${list.map(placeCard).join("")}</div>` : empty("Aucune adresse pour ce filtre.")}
      </section>`;
  }

  function place(id) {
    const p = DATA.places.find((x) => x.id === id);
    if (!p) return notFound();
    document.title = p.name + " · Publitarium Events";
    main.innerHTML = `
      <article class="article fade-in">
        <a class="back" href="javascript:history.back()">${icon("back")} Retour</a>
        <div class="visual" style="--g:${grad(p.category)}">${icon(cat(p.category).icon, "v-icon")}<div><span class="tag">${esc(p.type)}</span> <span class="tag">${esc(p.city)}</span></div></div>
        <h1>${esc(p.name)}</h1>
        <p class="lead">${esc(p.description)}</p>
        <dl class="info-list">
          <div><dt>Quartier</dt><dd style="margin:0">${esc(p.area)}, ${esc(p.city)}</dd></div>
          <div><dt>Budget</dt><dd style="margin:0">${esc(p.price)}</dd></div>
          <div><dt>À ne pas manquer</dt><dd style="margin:0">${esc(p.mustTry)}</dd></div>
        </dl>
        <div class="article-actions">
          <a class="btn" href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(p.name + ", " + p.city)}" target="_blank" rel="noopener">${icon("pin")} Voir sur la carte</a>
          <button class="btn btn-ghost" data-fav-inline="p:${esc(p.id)}">${icon(favs.has("p:" + p.id) ? "heart-fill" : "heart")} ${favs.has("p:" + p.id) ? "Dans vos favoris" : "Ajouter aux favoris"}</button>
          <button class="btn btn-ghost" data-share>${icon("share")} Partager</button>
        </div>
      </article>`;
  }

  function search(q = "") {
    main.innerHTML = `
      <section class="section fade-in">
        <div class="search-wrap"><input class="search-input" type="search" id="q" placeholder="Festival, rooftop, Essaouira, hammam…" value="${esc(q)}" aria-label="Rechercher" autocomplete="off"></div>
        <div id="results"></div>
      </section>`;
    const input = $("#q");
    const norm = (s) => String(s || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");
    const run = () => {
      const t = norm(input.value.trim());
      const res = $("#results");
      if (!t) { res.innerHTML = `<p class="muted">Tapez un mot-clé pour chercher dans les articles, l'agenda et les adresses.</p>`; return; }
      const hit = (o, fields) => fields.some((f) => norm([].concat(o[f]).join(" ")).includes(t));
      const a = DATA.articles.filter((x) => hit(x, ["title", "excerpt", "city", "tags", "content"]) || norm(cat(x.category).name).includes(t));
      const e = DATA.events.filter((x) => hit(x, ["title", "description", "city", "venue"]) || norm(cat(x.category).name).includes(t));
      const p = DATA.places.filter((x) => hit(x, ["name", "description", "city", "type", "area"]));
      res.innerHTML = (a.length + e.length + p.length === 0) ? empty("Aucun résultat.") :
        (e.length ? `<div class="section">${sectionHead("Agenda")}<div class="grid">${e.map(eventCard).join("")}</div></div>` : "") +
        (a.length ? `<div class="section">${sectionHead("Articles")}<div class="grid">${a.map(articleCard).join("")}</div></div>` : "") +
        (p.length ? `<div class="section">${sectionHead("Adresses")}<div class="grid">${p.map(placeCard).join("")}</div></div>` : "");
    };
    input.addEventListener("input", run);
    run();
    if (!q) input.focus();
  }

  function favorites() {
    const a = DATA.articles.filter((x) => favs.has("a:" + x.id));
    const e = DATA.events.filter((x) => favs.has("e:" + x.id));
    const p = DATA.places.filter((x) => favs.has("p:" + x.id));
    main.innerHTML = `
      <section class="section fade-in">
        <span class="kicker">Favoris</span>
        <h1 style="font-size:34px;margin:4px 0 16px">Vos coups de cœur</h1>
        ${a.length + e.length + p.length === 0 ? empty("Touchez le cœur sur un article, un événement ou une adresse pour le retrouver ici.") : ""}
        ${e.length ? `<div class="section">${sectionHead("Événements")}<div class="grid">${e.map(eventCard).join("")}</div></div>` : ""}
        ${a.length ? `<div class="section">${sectionHead("Articles")}<div class="grid">${a.map(articleCard).join("")}</div></div>` : ""}
        ${p.length ? `<div class="section">${sectionHead("Adresses")}<div class="grid">${p.map(placeCard).join("")}</div></div>` : ""}
      </section>`;
  }

  function about() {
    main.innerHTML = `
      <article class="article fade-in">
        <span class="kicker">À propos</span>
        <h1>Publitarium Events</h1>
        <p class="lead">Le magazine mobile des sorties au Maroc : actualité culturelle, agenda, festivals, rendez-vous à réserver et bonnes adresses, de Casablanca à Essaouira.</p>
        <div class="body">
          <p>Chaque article est accompagné d'un résumé vocal lu par Oulbig : touchez le bouton lecture pour écouter l'essentiel en moins d'une minute, en marchant, en conduisant ou en cuisinant.</p>
          <p>Les dates des événements sont communiquées à titre indicatif : vérifiez-les toujours auprès des organisateurs avant de vous déplacer.</p>
          <p>Vous organisez un événement ou tenez une bonne adresse ? Écrivez-nous via le formulaire ci-dessous.</p>
        </div>
        <form class="form" data-form="contact">
          <h2>Nous contacter</h2>
          <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
          <label>Nom<input name="name" required autocomplete="name"></label>
          <label>Email<input name="email" type="email" required autocomplete="email"></label>
          <label>Message<textarea name="notes" rows="4" required></textarea></label>
          <button class="btn" type="submit">Envoyer</button>
          <p class="form-msg" role="status"></p>
        </form>
      </article>`;
  }

  function notFound() {
    main.innerHTML = `<section class="section">${empty(`Page introuvable. <a href="#/" style="color:var(--accent);font-weight:700">Retour à l'accueil</a>`)}</section>`;
  }

  /* ---------- Villes ---------- */
  function renderCities() {
    const all = ["Toutes", ...DATA.cities];
    $("#cityBar").innerHTML = all.map((c) => `<button class="chip" aria-pressed="${c === city}" data-city="${esc(c)}">${c === "Toutes" ? "Tout le Maroc" : esc(c)}</button>`).join("");
  }

  /* ---------- Routeur ---------- */
  const routes = [
    [/^\/?$/, () => home(), "home"],
    [/^\/agenda(?:\/([\w-]+))?$/, (m) => agenda(m[1] || "all"), "agenda"],
    [/^\/categorie\/([\w-]+)$/, (m) => category(m[1]), ""],
    [/^\/article\/([\w-]+)$/, (m) => article(m[1]), ""],
    [/^\/evenement\/([\w-]+)$/, (m) => event(m[1]), "agenda"],
    [/^\/adresses(?:\/([\w-]+))?$/, (m) => places(m[1] || "all"), "adresses"],
    [/^\/adresse\/([\w-]+)$/, (m) => place(m[1]), "adresses"],
    [/^\/recherche(?:\/(.*))?$/, (m) => search(decodeURIComponent(m[1] || "")), "recherche"],
    [/^\/favoris$/, () => favorites(), "favoris"],
    [/^\/a-propos$/, () => about(), ""]
  ];
  function route() {
    const path = location.hash.replace(/^#/, "") || "/";
    document.title = "Publitarium Events — Actualité, agenda, festivals et bonnes adresses au Maroc";
    let tab = "";
    for (const [re, fn, t] of routes) {
      const m = path.match(re);
      if (m) { fn(m); tab = t; break; }
    }
    if (!routes.some(([re]) => re.test(path))) notFound();
    $$(".tabbar a").forEach((a) => a.classList.toggle("active", a.dataset.tab === tab));
    $$(".desk-nav a").forEach((a) => a.classList.toggle("active", a.getAttribute("href") === "#" + path));
    window.scrollTo(0, 0);
  }

  /* ---------- Actions ---------- */
  document.addEventListener("click", (ev) => {
    const t = ev.target.closest("button, a");
    if (!t) return;
    if (t.dataset.city) {
      city = t.dataset.city; store.set("city", city);
      renderCities(); route();
    } else if (t.dataset.fav) {
      ev.preventDefault(); toggleFav(t.dataset.fav);
    } else if (t.dataset.favInline) {
      toggleFav(t.dataset.favInline);
      const on = favs.has(t.dataset.favInline);
      t.innerHTML = `${icon(on ? "heart-fill" : "heart")} ${on ? "Dans vos favoris" : "Ajouter aux favoris"}`;
    } else if (t.dataset.listen) {
      const a = DATA.articles.find((x) => x.id === t.dataset.listen);
      if (player.id === a.id && player.speaking) player.toggle(); else player.play(a);
    } else if (t.hasAttribute("data-share")) {
      const data = { title: document.title, url: location.href };
      if (navigator.share) navigator.share(data).catch(() => {});
      else navigator.clipboard?.writeText(location.href).then(() => toast("Lien copié"));
    } else if (t.dataset.ics) {
      downloadIcs(DATA.events.find((x) => x.id === t.dataset.ics));
    }
  });

  function downloadIcs(e) {
    const d = (s) => s.replace(/-/g, "");
    const end = new Date(parseDate(e.endDate || e.startDate)); end.setDate(end.getDate() + 1);
    const clean = (s) => String(s || "").replace(/[,;\\]/g, (c) => "\\" + c).replace(/\n/g, "\\n");
    const ics = [
      "BEGIN:VCALENDAR", "VERSION:2.0", "PRODID:-//Publitarium Events//FR", "BEGIN:VEVENT",
      `UID:${e.id}@publitarium`, `DTSTAMP:${new Date().toISOString().replace(/[-:]/g, "").slice(0, 15)}Z`,
      `DTSTART;VALUE=DATE:${d(e.startDate)}`, `DTEND;VALUE=DATE:${end.toISOString().slice(0, 10).replace(/-/g, "")}`,
      `SUMMARY:${clean(e.title)}`, `LOCATION:${clean(e.venue + ", " + e.city)}`, `DESCRIPTION:${clean(e.description)}`,
      "END:VEVENT", "END:VCALENDAR"
    ].join("\r\n");
    const a = document.createElement("a");
    a.href = URL.createObjectURL(new Blob([ics], { type: "text/calendar" }));
    a.download = e.id + ".ics";
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }

  document.addEventListener("submit", async (ev) => {
    const form = ev.target.closest("[data-form]");
    if (!form) return;
    ev.preventDefault();
    const msg = $(".form-msg", form);
    const btn = $("button[type=submit]", form);
    const body = Object.fromEntries(new FormData(form));
    body.type = form.dataset.form;
    if (body.type === "newsletter") body.city = city;
    btn.disabled = true; msg.className = "form-msg"; msg.textContent = "Envoi…";
    try {
      const r = await fetch("api/submit.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(body) });
      const j = await r.json().catch(() => ({}));
      if (!r.ok || !j.ok) throw new Error(j.error || "Erreur d'envoi");
      form.reset();
      msg.classList.add("ok");
      msg.textContent = body.type === "rendezvous" ? `Demande envoyée ! Référence : ${j.reference}. Nous vous recontactons rapidement.`
        : body.type === "newsletter" ? "Merci, vous êtes inscrit·e !" : "Message envoyé, merci !";
    } catch (err) {
      msg.classList.add("err");
      msg.textContent = err.message || "Erreur d'envoi, réessayez.";
    } finally { btn.disabled = false; }
  });

  /* ---------- Démarrage ---------- */
  $("#year").textContent = new Date().getFullYear();
  window.addEventListener("hashchange", () => { route(); });
  fetch("data/content.json?v=" + Date.now().toString().slice(0, 7))
    .then((r) => r.json())
    .then((d) => {
      DATA = d;
      if (city !== "Toutes" && !DATA.cities.includes(city)) city = "Toutes";
      renderCities(); route();
    })
    .catch(() => { main.innerHTML = empty("Impossible de charger le contenu. Rechargez la page."); });

  if ("serviceWorker" in navigator && location.protocol === "https:") {
    navigator.serviceWorker.register("sw.js").catch(() => {});
  }
})();
