/* Publitarium Events — thème WordPress : thème clair/sombre, favoris, lecteur vocal Oulbig, partage, calendrier. */
(() => {
  "use strict";
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const store = {
    get: (k, d) => { try { return JSON.parse(localStorage.getItem("pe_" + k)) ?? d; } catch { return d; } },
    set: (k, v) => { try { localStorage.setItem("pe_" + k, JSON.stringify(v)); } catch {} }
  };
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const icon = (id) => `<svg aria-hidden="true"><use href="#i-${id}"/></svg>`;
  function toast(msg) {
    const t = $("#toast"); if (!t) return;
    t.textContent = msg; t.classList.add("show");
    clearTimeout(toast._t); toast._t = setTimeout(() => t.classList.remove("show"), 2200);
  }

  /* Thème */
  $("#themeBtn")?.addEventListener("click", () => {
    const t = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
    document.documentElement.dataset.theme = t; store.set("theme", t);
  });

  /* Favoris : objets {k, t, u, m} gardés sur l'appareil */
  let favs = store.get("wpfavs", []);
  const has = (k) => favs.some((f) => f.k === k);
  function toggle(obj) {
    favs = has(obj.k) ? favs.filter((f) => f.k !== obj.k) : [obj, ...favs];
    store.set("wpfavs", favs); paint();
    toast(has(obj.k) ? "Ajouté aux favoris" : "Retiré des favoris");
    renderFavs();
  }
  function paint() {
    $$("[data-fav]").forEach((b) => {
      const on = has(JSON.parse(b.dataset.fav).k);
      b.classList.toggle("on", on); b.setAttribute("aria-pressed", on); b.innerHTML = icon(on ? "heart-fill" : "heart");
    });
    $$("[data-fav-inline]").forEach((b) => {
      const on = has(JSON.parse(b.dataset.favInline).k);
      b.innerHTML = `${icon(on ? "heart-fill" : "heart")} ${on ? "Dans vos favoris" : "Ajouter aux favoris"}`;
    });
  }
  function renderFavs() {
    const box = $("#favList"); if (!box) return;
    box.innerHTML = favs.length
      ? favs.map((f) => `<div class="event-card" style="grid-template-columns:1fr auto"><div><div class="meta"><span>${esc(f.m)}</span></div><h3><a href="${esc(f.u)}">${esc(f.t)}</a></h3></div><button class="fav-btn on" style="position:static" data-fav='${esc(JSON.stringify(f))}' aria-label="Retirer">${icon("heart-fill")}</button></div>`).join("")
      : `<div class="empty">Touchez le cœur sur un article, un événement ou une adresse pour le retrouver ici.</div>`;
  }

  /* Lecteur vocal */
  const P = $("#player");
  const player = {
    audio: null, speaking: false, paused: false, rate: 1, id: null, text: "", timer: null, start: 0, est: 0, pausedAt: 0,
    voice() {
      const v = speechSynthesis.getVoices();
      return v.find((x) => /fr[-_]FR/i.test(x.lang) && /(google|amélie|thomas|denise|henri|natural)/i.test(x.name)) || v.find((x) => /^fr/i.test(x.lang)) || null;
    },
    play(btn) {
      this.stop();
      this.id = btn.dataset.listen; this.btn = btn;
      $("#playerTitle").textContent = btn.dataset.title || "";
      P.hidden = false;
      if (btn.dataset.audio) {
        this.audio = new Audio(btn.dataset.audio); this.audio.playbackRate = this.rate;
        this.audio.addEventListener("timeupdate", () => this.progress(this.audio.currentTime / (this.audio.duration || 1)));
        this.audio.addEventListener("ended", () => this.done());
        this.audio.play().catch(() => toast("Lecture audio impossible"));
      } else if ("speechSynthesis" in window) {
        this.text = btn.dataset.text || ""; this.speak();
      } else { toast("La lecture vocale n'est pas disponible sur ce navigateur"); P.hidden = true; return; }
      this.speaking = true; this.paused = false; this.paint();
    },
    speak() {
      speechSynthesis.cancel();
      const u = new SpeechSynthesisUtterance(this.text);
      u.lang = "fr-FR"; u.rate = this.rate; const v = this.voice(); if (v) u.voice = v;
      u.onend = () => { if (this.utter === u) this.done(); };
      this.utter = u; this.est = (this.text.split(/\s+/).length / (2.6 * this.rate)) * 1000; this.start = Date.now();
      clearInterval(this.timer); this.timer = setInterval(() => { if (!this.paused) this.progress((Date.now() - this.start) / this.est); }, 300);
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
      if (this.audio) this.audio.playbackRate = this.rate; else if (this.speaking && !this.paused) this.speak();
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
      const t = $("#playerToggle"); if (t) { t.innerHTML = icon(playing ? "pause" : "play"); t.setAttribute("aria-label", playing ? "Pause" : "Lecture"); }
      $$("[data-voice-card]").forEach((c) => {
        const mine = c.dataset.voiceCard === this.id && playing;
        c.classList.toggle("speaking", mine);
        const b = $("button", c); b.innerHTML = icon(mine ? "pause" : "play");
      });
    }
  };
  if (P) {
    $("#playerToggle").addEventListener("click", () => (!player.speaking && player.btn ? player.play(player.btn) : player.toggle()));
    $("#playerSpeed").addEventListener("click", () => player.cycleRate());
    $("#playerClose").addEventListener("click", () => { player.stop(); P.hidden = true; });
  }

  function ics(e) {
    const d = (s) => s.replace(/-/g, "");
    const end = new Date((e.end || e.start) + "T12:00:00"); end.setDate(end.getDate() + 1);
    const clean = (s) => String(s || "").replace(/[,;\\]/g, (c) => "\\" + c).replace(/\n/g, "\\n");
    const body = ["BEGIN:VCALENDAR", "VERSION:2.0", "PRODID:-//Publitarium Events//FR", "BEGIN:VEVENT", `UID:${e.id}@publitarium`,
      `DTSTAMP:${new Date().toISOString().replace(/[-:]/g, "").slice(0, 15)}Z`, `DTSTART;VALUE=DATE:${d(e.start)}`,
      `DTEND;VALUE=DATE:${end.toISOString().slice(0, 10).replace(/-/g, "")}`, `SUMMARY:${clean(e.title)}`, `LOCATION:${clean(e.loc)}`,
      `DESCRIPTION:${clean(e.desc)}`, "END:VEVENT", "END:VCALENDAR"].join("\r\n");
    const a = document.createElement("a");
    a.href = URL.createObjectURL(new Blob([body], { type: "text/calendar" })); a.download = (e.id || "evenement") + ".ics"; a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }

  document.addEventListener("click", (ev) => {
    const t = ev.target.closest("button, a");
    if (!t) return;
    if (t.dataset.fav) { ev.preventDefault(); toggle(JSON.parse(t.dataset.fav)); }
    else if (t.dataset.favInline) toggle(JSON.parse(t.dataset.favInline));
    else if (t.dataset.listen) { player.id === t.dataset.listen && player.speaking ? player.toggle() : player.play(t); }
    else if (t.hasAttribute("data-share")) {
      const data = { title: document.title, url: location.href };
      if (navigator.share) navigator.share(data).catch(() => {}); else navigator.clipboard?.writeText(location.href).then(() => toast("Lien copié"));
    } else if (t.dataset.ics) ics(JSON.parse(t.dataset.ics));
  });

  paint(); renderFavs();
})();
