/* Back-office Publitarium Events */
(() => {
  "use strict";
  const app = document.getElementById("app");
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  let csrf = "";
  let C = null; // contenu
  let view = "events";

  function toast(msg, err = false) {
    const t = document.getElementById("toast");
    t.textContent = msg; t.className = "toast show" + (err ? " err" : "");
    clearTimeout(toast._t); toast._t = setTimeout(() => (t.className = "toast"), 2600);
  }
  async function api(action, body, opts = {}) {
    const init = { method: body === undefined ? "GET" : "POST", headers: { "X-CSRF": csrf }, credentials: "same-origin" };
    if (body instanceof FormData) init.body = body;
    else if (body !== undefined) { init.body = JSON.stringify(body); init.headers["Content-Type"] = "application/json"; }
    const r = await fetch("api.php?action=" + action + (opts.qs || ""), init);
    const j = await r.json().catch(() => ({ ok: false, error: "Réponse invalide du serveur" }));
    if (r.status === 401 && action !== "login") { start(); throw new Error(j.error); }
    if (!j.ok) throw new Error(j.error || "Erreur");
    return j;
  }

  /* ---------- Connexion ---------- */
  async function start() {
    const s = await (await fetch("api.php?action=state", { credentials: "same-origin" })).json();
    csrf = s.csrf;
    if (s.setup) return authForm(true);
    if (!s.logged) return authForm(false);
    C = (await api("content")).content;
    shell();
  }
  function authForm(setup) {
    app.innerHTML = `
      <form class="auth" id="auth">
        <div class="logo">P</div>
        <h1>${setup ? "Créer le mot de passe" : "Back-office"}</h1>
        <p class="hint" style="font-size:14px">${setup ? "Première connexion : choisissez le mot de passe administrateur (10 caractères minimum). Notez-le bien." : "Publitarium Events"}</p>
        <label class="f" style="margin:14px 0">Mot de passe<input type="password" name="password" required minlength="${setup ? 10 : 1}" autocomplete="${setup ? "new-password" : "current-password"}" autofocus></label>
        ${setup ? `<label class="f" style="margin-bottom:14px">Confirmer<input type="password" name="confirm" required autocomplete="new-password"></label>` : ""}
        <button class="btn" style="width:100%;justify-content:center">${setup ? "Créer et entrer" : "Se connecter"}</button>
        <p class="msg err" id="authMsg" style="margin-top:10px"></p>
      </form>`;
    document.getElementById("auth").addEventListener("submit", async (e) => {
      e.preventDefault();
      const f = new FormData(e.target);
      const m = document.getElementById("authMsg");
      if (setup && f.get("password") !== f.get("confirm")) { m.textContent = "Les deux mots de passe ne correspondent pas."; return; }
      e.target.querySelector("button").disabled = true;
      try { await api(setup ? "setup" : "login", { password: f.get("password") }); await start(); }
      catch (err) { m.textContent = err.message; e.target.querySelector("button").disabled = false; }
    });
  }

  /* ---------- Structure ---------- */
  const NAV = [
    ["events", "Événements & festivals"], ["articles", "Articles"], ["places", "Bonnes adresses"],
    ["rendezvous", "Rendez-vous reçus"], ["newsletter", "Newsletter"], ["contact", "Messages"],
    ["appearance", "Couleurs & réglages"], ["account", "Mot de passe"]
  ];
  function shell() {
    app.innerHTML = `
      <div class="shell">
        <aside class="side">
          <div class="brand"><span class="logo">P</span>Back-office</div>
          <nav>${NAV.map(([k, l]) => `<button class="nav" data-nav="${k}">${l}</button>`).join("")}</nav>
          <div class="links"><a href="../" target="_blank" rel="noopener">Voir le site ↗</a><button data-logout>Déconnexion</button></div>
        </aside>
        <main class="content" id="view"></main>
      </div>`;
    app.addEventListener("click", async (e) => {
      const b = e.target.closest("[data-nav],[data-logout]");
      if (!b) return;
      if (b.dataset.nav) { view = b.dataset.nav; render(); }
      else { await api("logout", {}); start(); }
    });
    render();
  }
  function render() {
    document.querySelectorAll("[data-nav]").forEach((b) => b.classList.toggle("active", b.dataset.nav === view));
    const v = document.getElementById("view");
    if (SCHEMAS[view]) listView(v, view);
    else if (["rendezvous", "newsletter", "contact"].includes(view)) submissionsView(v, view);
    else if (view === "appearance") appearanceView(v);
    else accountView(v);
    window.scrollTo(0, 0);
  }

  /* ---------- Schémas des formulaires ---------- */
  const catOptions = () => (C.categories || []).map((c) => [c.slug, c.name]);
  const cityOptions = () => [...(C.cities || []), "National"].map((c) => [c, c]);
  const SCHEMAS = {
    events: {
      title: "Événements & festivals", one: "un événement", key: "title",
      fields: [
        ["title", "Titre", "text", { req: true, full: true }],
        ["category", "Rubrique", "select", { options: catOptions }],
        ["city", "Ville", "select", { options: cityOptions }],
        ["startDate", "Date de début", "date", { req: true }],
        ["endDate", "Date de fin", "date", { hint: "Laisser vide pour un seul jour" }],
        ["recurring", "Récurrence", "text", { hint: "Ex. « Tous les jours », pour les activités à réserver" }],
        ["dateNote", "Note sur la date", "text", { hint: "Ex. « Dates à confirmer »" }],
        ["venue", "Lieu / adresse", "text", { req: true, full: true }],
        ["price", "Tarif", "text"],
        ["slots", "Créneaux de rendez-vous", "text", { hint: "Séparés par des virgules : 10:00, 14:00. Rempli = formulaire de réservation affiché" }],
        ["url", "Lien officiel / billetterie", "url", { full: true }],
        ["description", "Description", "textarea", { full: true }],
        ["image", "Photo", "image", { full: true }],
        ["featured", "Mettre en avant", "check"]
      ],
      meta: (x) => `${pill(x.category)}${esc(x.city || "")} · ${x.recurring ? esc(x.recurring) : fmtDate(x.startDate) + (x.endDate ? " → " + fmtDate(x.endDate) : "")}${isPast(x) ? ' <span class="pill past">passé</span>' : ""}`,
      sort: (a, b) => (a.startDate || "").localeCompare(b.startDate || "")
    },
    articles: {
      title: "Articles", one: "un article", key: "title",
      fields: [
        ["title", "Titre", "text", { req: true, full: true }],
        ["category", "Rubrique", "select", { options: catOptions }],
        ["city", "Ville", "select", { options: cityOptions }],
        ["date", "Date de publication", "date"],
        ["author", "Auteur", "text"],
        ["readTime", "Temps de lecture (min)", "number"],
        ["excerpt", "Chapô (résumé court)", "textarea", { req: true, full: true }],
        ["content", "Texte de l'article", "textarea", { full: true, rows: 12, hint: "Séparez les paragraphes par une ligne vide" }],
        ["voiceSummary", "Résumé vocal d'Oulbig (texte lu)", "textarea", { full: true, hint: "Lu par la voix du navigateur si aucun fichier audio n'est fourni" }],
        ["audioUrl", "Enregistrement audio d'Oulbig (MP3)", "audio", { full: true, hint: "Prioritaire sur la lecture automatique" }],
        ["image", "Photo", "image", { full: true }],
        ["tags", "Mots-clés", "text", { full: true, hint: "Séparés par des virgules" }],
        ["featured", "À la une", "check"]
      ],
      meta: (x) => `${pill(x.category)}${esc(x.city || "")} · ${fmtDate(x.date)}${x.audioUrl ? " · 🎙️ audio" : ""}`,
      sort: (a, b) => (b.date || "").localeCompare(a.date || "")
    },
    places: {
      title: "Bonnes adresses", one: "une adresse", key: "name",
      fields: [
        ["name", "Nom", "text", { req: true, full: true }],
        ["category", "Rubrique", "select", { options: catOptions }],
        ["type", "Type", "text", { hint: "Ex. Rooftop, Café, Riad & Spa" }],
        ["city", "Ville", "select", { options: cityOptions }],
        ["area", "Quartier", "text"],
        ["price", "Budget", "select", { options: () => [["€", "€"], ["€€", "€€"], ["€€€", "€€€"]] }],
        ["address", "Adresse", "text", { full: true }],
        ["mustTry", "À ne pas manquer", "text", { full: true }],
        ["description", "Description", "textarea", { full: true }],
        ["image", "Photo", "image", { full: true }]
      ],
      meta: (x) => `${pill(x.category)}${esc(x.city || "")} · ${esc(x.area || "")} · ${esc(x.price || "")}`,
      sort: (a, b) => (a.city || "").localeCompare(b.city || "") || (a.name || "").localeCompare(b.name || "")
    }
  };
  const catOf = (s) => (C.categories || []).find((c) => c.slug === s) || { name: s || "—", color: "#888" };
  const pill = (s) => `<span class="pill" style="background:${esc(catOf(s).color)}">${esc(catOf(s).name)}</span>`;
  const fmtDate = (s) => (s ? new Date(s + "T12:00:00").toLocaleDateString("fr-FR", { day: "numeric", month: "short", year: "numeric" }) : "");
  const isPast = (x) => !x.recurring && (x.endDate || x.startDate) && new Date((x.endDate || x.startDate) + "T23:59:59") < new Date();

  /* ---------- Listes ---------- */
  function listView(v, col, q = "") {
    const S = SCHEMAS[col];
    const all = [...(C[col] || [])].sort(S.sort);
    const norm = (s) => String(s).toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");
    const items = q ? all.filter((x) => norm(JSON.stringify(x)).includes(norm(q))) : all;
    v.innerHTML = `
      <div class="head"><h1>${S.title}</h1><div class="tools">
        <input type="search" id="q" placeholder="Rechercher…" value="${esc(q)}" style="width:200px">
        <button class="btn" data-new>+ Ajouter ${S.one}</button></div></div>
      <div class="list">${items.length ? items.map((x) => `
        <div class="row"><div><div class="t">${x.featured ? "★ " : ""}${esc(x[S.key])}</div><div class="m">${S.meta(x)}</div></div>
        <div class="tools"><button class="btn ghost" data-edit="${esc(x.id)}">Modifier</button></div></div>`).join("") : `<div class="empty">Aucun élément.</div>`}
      </div>`;
    v.querySelector("#q").addEventListener("input", (e) => { const pos = e.target.selectionStart; listView(v, col, e.target.value); const n = v.querySelector("#q"); n.focus(); n.setSelectionRange(pos, pos); });
    v.querySelector("[data-new]").onclick = () => editView(v, col, null);
    v.querySelectorAll("[data-edit]").forEach((b) => (b.onclick = () => editView(v, col, C[col].find((x) => x.id === b.dataset.edit))));
  }

  /* ---------- Édition ---------- */
  function field([name, label, type, o = {}], item) {
    let val = item?.[name] ?? "";
    if (Array.isArray(val)) val = name === "content" ? val.join("\n\n") : val.join(", ");
    const lab = `<span>${label}${o.req ? ' <span class="req">*</span>' : ""}${o.hint ? ` <span class="hint">— ${o.hint}</span>` : ""}</span>`;
    const full = o.full ? ' style="grid-column:1/-1"' : "";
    if (type === "check") return `<label class="check"${full}><input type="checkbox" name="${name}" ${val ? "checked" : ""}> ${label}</label>`;
    if (type === "select") {
      const opts = o.options();
      if (!item && name === "category" && opts.length) val = val || opts[0][0];
      return `<label class="f"${full}>${lab}<select name="${name}">${opts.map(([k, l]) => `<option value="${esc(k)}" ${k === val ? "selected" : ""}>${esc(l)}</option>`).join("")}</select></label>`;
    }
    if (type === "textarea") return `<label class="f"${full}>${lab}<textarea name="${name}" rows="${o.rows || 4}" ${o.req ? "required" : ""}>${esc(val)}</textarea></label>`;
    if (type === "image" || type === "audio") {
      const accept = type === "image" ? "image/jpeg,image/png,image/webp" : "audio/*";
      const preview = val ? (type === "image" ? `<img src="../${esc(val)}" alt="">` : `<audio controls src="../${esc(val)}"></audio>`) : "";
      return `<div class="f"${full}><label class="f">${lab}</label>
        <div class="media" data-media="${name}">${preview}
          <input type="hidden" name="${name}" value="${esc(val)}">
          <label class="btn ghost" style="cursor:pointer">${val ? "Remplacer" : "Choisir un fichier"}<input type="file" accept="${accept}" hidden data-upload="${name}"></label>
          ${val ? `<button type="button" class="btn ghost" data-clear="${name}">Retirer</button>` : ""}
        </div></div>`;
    }
    const t = type === "date" ? "date" : type === "number" ? "number" : type === "url" ? "url" : "text";
    return `<label class="f"${full}>${lab}<input type="${t}" name="${name}" value="${esc(val)}" ${o.req ? "required" : ""}></label>`;
  }

  function editView(v, col, item) {
    const S = SCHEMAS[col];
    v.innerHTML = `
      <div class="head"><h1>${item ? "Modifier" : "Ajouter"} ${S.one}</h1><button class="btn ghost" data-back>← Retour à la liste</button></div>
      <form class="editor" id="ed" novalidate>
        <div class="grid2">${S.fields.map((f) => field(f, item)).join("")}</div>
        <p class="msg err" id="edMsg"></p>
        <div class="actions">
          ${item ? `<button type="button" class="btn danger" data-del>Supprimer</button>` : ""}
          ${item && col !== "places" ? `<a class="btn ghost" target="_blank" rel="noopener" href="../#/${col === "events" ? "evenement" : "article"}/${esc(item.id)}">Voir ↗</a>` : ""}
          ${item && col === "places" ? `<a class="btn ghost" target="_blank" rel="noopener" href="../#/adresse/${esc(item.id)}">Voir ↗</a>` : ""}
          <button class="btn">Enregistrer</button>
        </div>
      </form>`;
    const form = v.querySelector("#ed");
    v.querySelector("[data-back]").onclick = () => listView(v, col);
    form.addEventListener("change", async (e) => {
      const inp = e.target.closest("[data-upload]");
      if (!inp || !inp.files[0]) return;
      const name = inp.dataset.upload;
      const fd = new FormData(); fd.append("file", inp.files[0]);
      toast("Envoi du fichier…");
      try {
        const j = await api("upload", fd);
        const box = form.querySelector(`[data-media="${name}"]`);
        box.querySelector("input[type=hidden]").value = j.url;
        const isImg = /\.(jpe?g|png|webp)$/i.test(j.url);
        box.querySelector("img,audio")?.remove();
        box.insertAdjacentHTML("afterbegin", isImg ? `<img src="../${esc(j.url)}" alt="">` : `<audio controls src="../${esc(j.url)}"></audio>`);
        toast("Fichier envoyé — pensez à enregistrer");
      } catch (err) { toast(err.message, true); }
      inp.value = "";
    });
    form.addEventListener("click", async (e) => {
      const c = e.target.closest("[data-clear]");
      if (c) { const box = form.querySelector(`[data-media="${c.dataset.clear}"]`); box.querySelector("input[type=hidden]").value = ""; box.querySelector("img,audio")?.remove(); c.remove(); }
      if (e.target.closest("[data-del]")) {
        if (!confirm(`Supprimer « ${item[S.key]} » ? Cette action est définitive.`)) return;
        try { await api("delete", { collection: col, id: item.id }); C = (await api("content")).content; toast("Supprimé"); listView(v, col); }
        catch (err) { toast(err.message, true); }
      }
    });
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const msg = v.querySelector("#edMsg");
      const missing = S.fields.filter(([n, , , o]) => o?.req && !form.elements[n].value.trim()).map(([, l]) => l);
      if (missing.length) { msg.textContent = "À remplir : " + missing.join(", "); return; }
      const data = { id: item?.id || "" };
      S.fields.forEach(([n, , t]) => { const el = form.elements[n]; data[n] = t === "check" ? el.checked : el.value; });
      const btn = form.querySelector(".actions .btn:last-child"); btn.disabled = true;
      try {
        const j = await api("save", { collection: col, item: data });
        C = (await api("content")).content;
        toast("Enregistré ✓");
        editView(v, col, C[col].find((x) => x.id === j.id));
      } catch (err) { msg.textContent = err.message; btn.disabled = false; }
    });
  }

  /* ---------- Demandes reçues ---------- */
  const SUB_COLS = {
    rendezvous: [["at", "Reçu le"], ["reference", "Réf."], ["event", "Activité"], ["date", "Date"], ["slot", "Créneau"], ["guests", "Pers."], ["name", "Nom"], ["phone", "Téléphone"], ["email", "Email"], ["notes", "Message"]],
    newsletter: [["at", "Inscrit le"], ["email", "Email"], ["city", "Ville"]],
    contact: [["at", "Reçu le"], ["name", "Nom"], ["email", "Email"], ["message", "Message"]]
  };
  async function submissionsView(v, type) {
    const title = NAV.find(([k]) => k === type)[1];
    v.innerHTML = `<div class="head"><h1>${title}</h1><div class="tools"><a class="btn ghost" href="api.php?action=submissions&type=${type}&format=csv">Exporter (Excel / CSV)</a></div></div><p class="loading">Chargement…</p>`;
    try {
      const { rows } = await api("submissions", undefined, { qs: "&type=" + type });
      const cols = SUB_COLS[type];
      const cell = (r, k) => k === "at" ? new Date(r.at).toLocaleString("fr-FR", { dateStyle: "short", timeStyle: "short" })
        : k === "phone" && r.phone ? `<a href="tel:${esc(r.phone)}">${esc(r.phone)}</a> · <a href="https://wa.me/${esc(String(r.phone).replace(/\D/g, ""))}" target="_blank" rel="noopener">WhatsApp</a>`
        : k === "email" && r.email ? `<a href="mailto:${esc(r.email)}">${esc(r.email)}</a>` : esc(r[k] ?? "");
      v.querySelector(".loading").outerHTML = rows.length
        ? `<p class="hint">${rows.length} au total</p><div class="table-wrap"><table><thead><tr>${cols.map(([, l]) => `<th>${l}</th>`).join("")}</tr></thead><tbody>${rows.map((r) => `<tr>${cols.map(([k]) => `<td>${cell(r, k)}</td>`).join("")}</tr>`).join("")}</tbody></table></div>`
        : `<div class="empty">Rien reçu pour le moment.</div>`;
    } catch (err) { toast(err.message, true); }
  }

  /* ---------- Couleurs & réglages ---------- */
  const PRESETS = [
    ["Medina Pop (défaut)", "#ff4d6d", "#3a56d4", "#f4a261"],
    ["Majorelle", "#2f4bd8", "#f2c14e", "#1b998b"],
    ["Terracotta", "#c8553d", "#2d6a4f", "#f2a65a"],
    ["Atlantique", "#0077b6", "#00b4d8", "#ffb703"],
    ["Safran nuit", "#e09f3e", "#540b0e", "#9e2a2b"],
    ["Menthe fraîche", "#2a9d8f", "#264653", "#e9c46a"]
  ];
  function appearanceView(v) {
    const s = { accent: "#ff4d6d", accent2: "#3a56d4", accent3: "#f4a261", tagline: "", ...(C.settings || {}) };
    v.innerHTML = `
      <div class="head"><h1>Couleurs & réglages</h1></div>
      <form id="ap">
        <div class="card">
          <h2>Palette du site</h2>
          <div class="swatches">${PRESETS.map(([n, a, b, c]) => `<button type="button" class="preset${a === s.accent && b === s.accent2 ? " on" : ""}" data-preset="${a},${b},${c}"><span class="dots"><i style="background:${a}"></i><i style="background:${b}"></i><i style="background:${c}"></i></span>${n}</button>`).join("")}</div>
          <div class="colors">
            <label><input type="color" name="accent" value="${esc(s.accent)}"> Couleur principale</label>
            <label><input type="color" name="accent2" value="${esc(s.accent2)}"> Couleur secondaire</label>
            <label><input type="color" name="accent3" value="${esc(s.accent3)}"> Couleur d'accent</label>
          </div>
          <div class="preview" id="pv"></div>
        </div>
        <div class="card">
          <h2>Couleurs des rubriques</h2>
          <div class="colors">${(C.categories || []).map((c) => `<label><input type="color" name="cat_${esc(c.slug)}" value="${esc(c.color)}"> ${esc(c.name)}</label>`).join("")}</div>
        </div>
        <div class="card">
          <h2>Villes</h2>
          <label class="f">Villes affichées dans le filtre <span class="hint">— séparées par des virgules, dans l'ordre</span><input type="text" name="cities" value="${esc((C.cities || []).join(", "))}"></label>
          <label class="f">Phrase d'accroche (pied de page)<input type="text" name="tagline" value="${esc(s.tagline)}" placeholder="L'actualité culturelle, les rendez-vous, les festivals et les bonnes adresses du Maroc."></label>
        </div>
        <div class="actions" style="justify-content:space-between">
          <button type="button" class="btn danger" data-reset>Revenir au contenu de départ…</button>
          <button class="btn">Enregistrer</button>
        </div>
      </form>`;
    const f = v.querySelector("#ap");
    const paint = () => {
      const [a, b, c] = [f.accent.value, f.accent2.value, f.accent3.value];
      v.querySelector("#pv").style.background = `linear-gradient(135deg, ${a}, ${b})`;
      v.querySelector("#pv").innerHTML = `<strong style="font:800 22px Georgia,serif">Aperçu : Festival Gnaoua</strong><span>Bouton et étiquettes du site</span><span><span style="background:#fff;color:${a};padding:6px 14px;border-radius:999px;font-weight:700">Réserver</span> <span style="background:${c};color:#1d1a24;padding:6px 14px;border-radius:999px;font-weight:700">À confirmer</span></span>`;
    };
    paint();
    f.addEventListener("input", paint);
    f.addEventListener("click", async (e) => {
      const p = e.target.closest("[data-preset]");
      if (p) { const [a, b, c] = p.dataset.preset.split(","); f.accent.value = a; f.accent2.value = b; f.accent3.value = c; f.querySelectorAll(".preset").forEach((x) => x.classList.toggle("on", x === p)); paint(); }
      if (e.target.closest("[data-reset]")) {
        const ok = prompt("Toutes vos modifications (événements, articles, adresses, couleurs) seront effacées. Tapez REINITIALISER pour confirmer.");
        if (ok !== "REINITIALISER") return;
        try { await api("reset", { confirm: ok }); C = (await api("content")).content; toast("Contenu de départ restauré"); appearanceView(v); } catch (err) { toast(err.message, true); }
      }
    });
    f.addEventListener("submit", async (e) => {
      e.preventDefault();
      const categoryColors = {};
      (C.categories || []).forEach((c) => (categoryColors[c.slug] = f.elements["cat_" + c.slug].value));
      try {
        await api("settings", { settings: { accent: f.accent.value, accent2: f.accent2.value, accent3: f.accent3.value, tagline: f.tagline.value }, cities: f.cities.value, categoryColors });
        C = (await api("content")).content; toast("Réglages enregistrés ✓");
      } catch (err) { toast(err.message, true); }
    });
  }

  function accountView(v) {
    v.innerHTML = `
      <div class="head"><h1>Mot de passe</h1></div>
      <form class="card" id="pw" style="max-width:420px">
        <label class="f">Mot de passe actuel<input type="password" name="current" required autocomplete="current-password"></label>
        <label class="f">Nouveau mot de passe (10 caractères min.)<input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
        <button class="btn">Changer le mot de passe</button>
        <p class="msg" id="pwMsg"></p>
      </form>`;
    v.querySelector("#pw").addEventListener("submit", async (e) => {
      e.preventDefault();
      const m = v.querySelector("#pwMsg");
      try { await api("password", { current: e.target.current.value, password: e.target.password.value }); m.className = "msg ok"; m.textContent = "Mot de passe modifié."; e.target.reset(); }
      catch (err) { m.className = "msg err"; m.textContent = err.message; }
    });
  }

  start().catch(() => { app.innerHTML = `<p class="loading">Le back-office a besoin de PHP : ouvrez-le sur le site en ligne.</p>`; });
})();
