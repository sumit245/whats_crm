"use strict";

require("dotenv").config();
const express = require("express");
const app     = express();
const http    = require("http");
const server  = http.createServer(app);
const { Server } = require("socket.io");
const io      = new Server(server, { pingInterval: 25000, pingTimeout: 10000 });
const port    = process.env.PORT_NODE || 3100;

const bodyParser = require("body-parser");
app.use(bodyParser.urlencoded({ extended: false, limit: "50mb" }));
app.use(bodyParser.json());

const SOCKET_SECRET = process.env.SOCKET_SECRET || "";

/**
 * POST /push
 * Body: { secret, room, event, payload }
 * Emits `event` with `payload` to all sockets in `room`.
 * room can be:
 *   "conv-{id}"   — conversation room (agents viewing that chat)
 *   "inbox-{uid}" — inbox room (all agents logged in under user account uid)
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

app.get("/health", (_req, res) => res.json({ ok: true }));

io.on("connection", (socket) => {
    socket.on("join", (room) => {
        socket.join(room);
    });

    socket.on("leave", (room) => {
        socket.leave(room);
    });

    socket.on("disconnect", () => {
        // socket.io handles cleanup automatically
    });
});

server.listen(port, () => {
    console.log(`Socket.io server running on port ${port}`);
});
