// pm2 process config for the Socket.io real-time relay.
//   Start:   pm2 start ecosystem.config.js
//   Logs:    pm2 logs mpwa-socket
//   Restart: pm2 restart mpwa-socket
//   Persist across reboots: pm2 save  (then run the command `pm2 startup` prints)
module.exports = {
    apps: [
        {
            name: "mpwa-socket",
            script: "server.js",
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: "200M",
            env: {
                PORT_NODE: 3100,
                // Set a shared secret here AND in the Laravel .env (SOCKET_SECRET)
                // to authenticate PHP -> Node pushes in production.
                SOCKET_SECRET: process.env.SOCKET_SECRET || "",
            },
        },
    ],
};
