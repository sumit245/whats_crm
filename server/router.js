"use strict";

/**
 * HTTP router for the Node socket service.
 *
 * This app runs on the Meta Cloud API — WhatsApp sending/receiving is handled
 * entirely by PHP (MetaCloudApiService + MetaWebhookController). The legacy
 * Baileys device/QR/send routes that used to live here are no longer used.
 *
 * The only live HTTP surface is POST /push (defined in server.js), which relays
 * real-time events from PHP to browsers via Socket.io rooms. This router just
 * exposes a health check and keeps server.js's `require("./server/router")`
 * satisfied.
 */

const express = require("express");
const router = express.Router();

router.get("/health", (req, res) => {
    res.json({ ok: true, service: "mpwa-socket", ts: Date.now() });
});

module.exports = router;
