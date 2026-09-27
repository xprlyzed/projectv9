/*
 | Laravel Echo + Reverb (WebSocket) bootstrap.
 | wss bağlantısı public host üzerinden (port 443) gelir; ingress bunu 3000'e,
 | 3000'deki nginx de "/app" yolunu Reverb'e (127.0.0.1:8080) yönlendirir.
 | Özel kanal yetkisi /broadcasting/auth (web session + CSRF) üzerinden yapılır.
*/
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { csrfHeaders } from '@/csrf';

window.Pusher = Pusher;

let echoInstance = null;

export function getEcho() {
    if (echoInstance) return echoInstance;

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    if (!key) return null; // Reverb yapılandırılmadıysa (no-op) → polling yedeği devrede

    const host = window.location.hostname;
    const port = Number(import.meta.env.VITE_REVERB_PORT || 443);
    const scheme = import.meta.env.VITE_REVERB_SCHEME || 'https';

    echoInstance = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                fetch('/broadcasting/auth', {
                    method: 'POST',
                    headers: csrfHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' }),
                    credentials: 'same-origin',
                    body: new URLSearchParams({ socket_id: socketId, channel_name: channel.name }),
                })
                    .then((r) => (r.ok ? r.json() : Promise.reject(r)))
                    .then((data) => callback(null, data))
                    .catch((err) => callback(err, null));
            },
        }),
    });

    return echoInstance;
}
