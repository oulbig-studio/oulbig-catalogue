// Relais des webhooks Meta vers les agents OULBIG.
//
// Meta (WhatsApp, Instagram, Facebook) appelle https://agents.almoqayada.com/webhook ;
// le relais vérifie la signature, garde ce qui concerne les agents, et réveille
// le workflow GitHub « Agents OULBIG » (repository_dispatch) qui fait le travail.
//
// Variables d'environnement (hPanel → Node.js → Variables d'environnement) :
//   GITHUB_TOKEN       jeton GitHub (fine-grained, dépôt oulbig-catalogue, Contents: Read and write)
//   GITHUB_REPO        oulbig-studio/oulbig-catalogue
//   META_VERIFY_TOKEN  mot de passe choisi pour la vérification du webhook dans Meta
//   META_APP_SECRET    clé secrète de l'app Meta (vérifie que les appels viennent de Meta)
//   META_PAGE_ID       ID de la Page (pour ignorer les commentaires de la Page elle-même)
//   WHATSAPP_PATRON    ton numéro, ex. 2126XXXXXXXX (seul ce numéro commande les agents)
//   WHATSAPP_TOKEN, WHATSAPP_PHONE_ID   (option) pour répondre « ⏳ » tout de suite
//   MOTS_CLES          mots qui déclenchent l'envoi de la BD (séparés par des virgules)
const crypto = require("crypto");
const express = require("express");

const app = express();
const env = (k, d = "") => (process.env[k] || d).trim();
const GRAPH = "https://graph.facebook.com/v23.0";

app.use(express.json({ verify: (req, _res, buf) => { req.brut = buf; } }));

function signatureOk(req) {
  const secret = env("META_APP_SECRET");
  if (!secret) return true; // à renseigner en production
  const attendue = "sha256=" + crypto.createHmac("sha256", secret).update(req.brut || "").digest("hex");
  const recue = req.get("X-Hub-Signature-256") || "";
  return recue.length === attendue.length &&
    crypto.timingSafeEqual(Buffer.from(recue), Buffer.from(attendue));
}

async function reveillerAgents(type, charge) {
  const r = await fetch(`https://api.github.com/repos/${env("GITHUB_REPO", "oulbig-studio/oulbig-catalogue")}/dispatches`, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${env("GITHUB_TOKEN")}`,
      Accept: "application/vnd.github+json",
      "X-GitHub-Api-Version": "2022-11-28",
      "User-Agent": "oulbig-agents-relais",
    },
    body: JSON.stringify({ event_type: type, client_payload: charge }),
  });
  if (!r.ok) console.error("GitHub", r.status, await r.text());
  return r.ok;
}

async function whatsapp(texte) {
  const token = env("WHATSAPP_TOKEN"), phone = env("WHATSAPP_PHONE_ID"), dest = env("WHATSAPP_PATRON");
  if (!token || !phone || !dest) return;
  const r = await fetch(`${GRAPH}/${phone}/messages`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
    body: JSON.stringify({ messaging_product: "whatsapp", to: dest, type: "text", text: { body: texte } }),
  });
  if (!r.ok) console.error("WhatsApp", r.status, await r.text());
}

const motsCles = () => env("MOTS_CLES", "bd,je veux,envoie,comic,livre,📚")
  .split(",").map((m) => m.trim().toLowerCase()).filter(Boolean);
const demandeBD = (t) => motsCles().some((m) => (t || "").toLowerCase().includes(m));

async function traiter(corps) {
  const objet = corps.object;
  for (const entree of corps.entry || []) {
    for (const ch of entree.changes || []) {
      const v = ch.value || {};
      if (objet === "whatsapp_business_account") {
        for (const m of v.messages || []) {
          if (m.from !== env("WHATSAPP_PATRON")) continue; // seul le patron commande
          if (m.type !== "text") { await whatsapp("J'ai besoin d'un message texte 🙂 (tape « aide »)"); continue; }
          const ok = await reveillerAgents("whatsapp", { texte: m.text.body.slice(0, 4000) });
          await whatsapp(ok ? "⏳ Reçu, les agents s'en occupent (environ 1 minute)…"
                            : "⚠️ Le relais n'a pas pu réveiller les agents (jeton GitHub ?).");
        }
      } else if (objet === "instagram" && ch.field === "comments") {
        if (demandeBD(v.text)) {
          await reveillerAgents("commentaire", { plateforme: "instagram", id: v.id,
            texte: (v.text || "").slice(0, 1000), auteur: (v.from || {}).username || "" });
        }
      } else if (objet === "page" && ch.field === "feed" && v.item === "comment" && v.verb === "add") {
        if ((v.from || {}).id === env("META_PAGE_ID")) continue;
        if (demandeBD(v.message)) {
          await reveillerAgents("commentaire", { plateforme: "facebook", id: v.comment_id,
            texte: (v.message || "").slice(0, 1000), auteur: (v.from || {}).name || "" });
        }
      }
    }
  }
}

app.get("/webhook", (req, res) => {
  if (req.query["hub.mode"] === "subscribe" && req.query["hub.verify_token"] === env("META_VERIFY_TOKEN")) {
    return res.send(String(req.query["hub.challenge"] || ""));
  }
  res.sendStatus(403);
});

app.post("/webhook", (req, res) => {
  if (!signatureOk(req)) return res.sendStatus(403);
  res.send("ok"); // Meta veut une réponse rapide ; le travail continue ensuite
  traiter(req.body || {}).catch((e) => console.error("Erreur relais :", e));
});

app.get("/", (_req, res) => res.send("Agents OULBIG : relais en ligne ✅"));

const port = process.env.PORT || 3000;
app.listen(port, () => console.log(`Relais agents OULBIG sur le port ${port}`));
