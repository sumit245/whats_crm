"use strict";

require("dotenv").config();
const express = require("express");
const app = express();
const http = require("http");
const server = http.createServer(app);
const { Server } = require("socket.io");
const io = new Server(server, { pingInterval: 25000, pingTimeout: 10000 });
const port = process.env.PORT_NODE || 3100;

const bodyParser = require("body-parser");
app.use(bodyParser.urlencoded({ extended: false, limit: "50mb" }));
app.use(bodyParser.json());
app.use(require("./server/router"));

// ── Socket.io room-based real-time push ───────────────────────────────────────
//
// PHP pushes events here via POST /push (Phase D).
// Clients join named rooms (e.g. "conv-42") and receive events in real-time,
// replacing the 3-second polling interval in the chat UI.

const SOCKET_SECRET = process.env.SOCKET_SECRET || "";

/**
 * POST /push
 * Body: { secret, room, event, payload }
 * Emits `event` with `payload` to all sockets in `room`.
 */
app.post("/push", (req, res) => {
    const { secret, room, event, payload } = req.body || {};

    if (SOCKET_SECRET && secret !== SOCKET_SECRET) {
        return res.status(403).json({ error: "Forbidden" });
    }

    if (!room || !event) {
        return res.status(400).json({ error: "room and event are required" });
    }

    io.to(room).emit(event, payload || {});
    res.json({ ok: true, room, event });
});

io.on("connection", (socket) => {
    // Client joins a conversation room to receive real-time messages
    socket.on("join", (room) => {
        socket.join(room);
    });

    socket.on("leave", (room) => {
        socket.leave(room);
    });

    socket.on("disconnect", () => {
        // no-op: socket.io handles cleanup
    });
});

server.listen(port, () => {
    console.log(`Socket.io server running on port ${port}`);
});
