<script>
import AppLayout from '@/Layouts/AppLayout.vue';
export default { layout: AppLayout };
</script>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, reactive, nextTick, onMounted, onBeforeUnmount, watch } from 'vue';
import { csrfHeaders } from '@/csrf';
import { getEcho } from '@/echo';

const page = usePage();

const props = defineProps({
    conversations: Array,
    active: Object,
    messages: Array,
    index_url: String,
});

const thread = ref(null);
const input = ref('');
const items = reactive([...(props.messages || [])]);
let pollTimer = null;
let echoChannel = null;
let echoChannelName = null;
const myId = page.props?.auth?.user?.id ?? null;

/* ---- Bildirim sesi (kullanıcı kapatabilir, localStorage) ---- */
const SOUND_KEY = 'artirdim_msg_sound';
const soundOn = ref(localStorage.getItem(SOUND_KEY) !== '0');
function toggleSound() {
    soundOn.value = !soundOn.value;
    localStorage.setItem(SOUND_KEY, soundOn.value ? '1' : '0');
}
let audioCtx = null;
function ensureAudio() {
    try {
        audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();
    } catch (e) { /* ses desteklenmiyorsa sessiz geç */ }
    return audioCtx;
}
function playBeep() {
    if (!soundOn.value) return;
    try {
        const ctx = ensureAudio();
        if (!ctx) return;
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = 'sine';
        o.frequency.setValueAtTime(660, ctx.currentTime);
        o.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12);
        g.gain.setValueAtTime(0.0001, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.15, ctx.currentTime + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.3);
        o.connect(g); g.connect(ctx.destination);
        o.start(); o.stop(ctx.currentTime + 0.3);
    } catch (e) { /* ses çalınamazsa sessiz geç */ }
}

function lastId() {
    return items.length ? items[items.length - 1].id : 0;
}

function scrollBottom() {
    nextTick(() => {
        if (thread.value) thread.value.scrollTop = thread.value.scrollHeight;
    });
}

function push(m) {
    if (items.some((x) => x.id === m.id)) return;
    items.push(m);
    scrollBottom();
}

function markMineRead() {
    items.forEach((m) => { if (m.mine) m.read = true; });
}

function send() {
    const body = input.value.trim();
    if (!body || !props.active) return;
    input.value = '';
    fetch(props.active.store_url, {
        method: 'POST',
        headers: csrfHeaders({ 'Content-Type': 'application/json' }, page.props.csrf_token),
        credentials: 'same-origin',
        body: JSON.stringify({ body }),
    })
        .then((r) => r.json())
        .then((m) => push(m))
        .catch(() => {});
}

// Karşıdan gelen okunmamışları sunucuda okundu işaretle → gönderene "görüldü" olayı gider.
function markReadOnServer() {
    if (!props.active?.read_url) return;
    fetch(props.active.read_url, {
        method: 'POST',
        headers: csrfHeaders({ 'Content-Type': 'application/json' }, page.props.csrf_token),
        credentials: 'same-origin',
    }).catch(() => {});
}

function poll() {
    if (!props.active) return;
    fetch(props.active.poll_url + '?after=' + lastId(), { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((d) => {
            const arr = d.messages || [];
            let gotIncoming = false;
            arr.forEach((m) => { push(m); if (!m.mine) gotIncoming = true; });
            if (gotIncoming) playBeep();
        })
        .catch(() => {});
}

// Reverb WebSocket: özel konuşma kanalını dinle (message.sent + messages.read).
function subscribeEcho() {
    const echo = getEcho();
    if (!echo || !props.active) return;
    const name = 'conversation.' + props.active.id;
    if (echoChannelName === name) return;
    if (echoChannel) { try { echo.leave(echoChannelName); } catch (e) {} echoChannel = null; }
    echoChannelName = name;
    echoChannel = echo.private(name)
        .listen('.message.sent', (e) => {
            if (e.conversation_id !== props.active?.id) return;
            const mine = e.sender_id === myId;
            push({ id: e.id, body: e.body, mine, time: e.time, read: false });
            if (!mine) {
                if (document.hidden || !props.active) playBeep(); else playBeep();
                markReadOnServer(); // sohbet açık → hemen okundu + gönderene bildir
            }
        })
        .listen('.messages.read', (e) => {
            if (e.conversation_id !== props.active?.id) return;
            if (e.reader_id !== myId) markMineRead(); // karşı taraf okudu → benim mesajlarım "görüldü"
        });
}

function teardownEcho() {
    const echo = getEcho();
    if (echo && echoChannelName) { try { echo.leave(echoChannelName); } catch (e) {} }
    echoChannel = null;
    echoChannelName = null;
}

onMounted(() => {
    // Tarayıcı autoplay politikası AudioContext'i "suspended" başlatır; ilk kullanıcı
    // etkileşiminde aç ki gelen mesajda bildirim sesi çalabilsin.
    const unlock = () => ensureAudio();
    document.addEventListener('click', unlock, { once: true });
    document.addEventListener('keydown', unlock, { once: true });
    if (props.active) {
        scrollBottom();
        subscribeEcho();
        pollTimer = setInterval(poll, 3000); // Reverb yoksa yakın-anlık yedek
    }
});

watch(() => props.active?.id, (id, old) => {
    if (id === old) return;
    items.splice(0, items.length, ...(props.messages || []));
    teardownEcho();
    subscribeEcho();
    if (props.active) { scrollBottom(); if (!pollTimer) pollTimer = setInterval(poll, 3000); }
});

onBeforeUnmount(() => {
    if (pollTimer) clearInterval(pollTimer);
    teardownEcho();
});
</script>

<template>
    <Head title="Mesajlar" />
    <div class="msg-page py-4">
        <div class="msg-layout au-card">

            <aside class="msg-list" :class="{ 'has-active': active }">
                <div class="msg-list-head">
                    <i class="bi bi-chat-dots"></i> Mesajlar
                </div>
                <div class="msg-list-scroll">
                    <template v-if="conversations.length">
                        <Link
                            v-for="c in conversations"
                            :key="c.id"
                            :href="c.url"
                            class="msg-conv"
                            :class="{ active: c.is_active }"
                            :data-testid="'conversation-item-' + c.id"
                        >
                            <div class="msg-avatar-wrap">
                                <img class="msg-avatar" :src="c.peer_avatar" :alt="c.peer_name">
                                <span v-if="c.peer_online" class="msg-online-dot" data-testid="conv-online-dot"></span>
                            </div>
                            <div class="msg-conv-info">
                                <div class="msg-conv-name">{{ c.peer_name }}</div>
                                <div class="msg-conv-last">{{ c.last_body }}</div>
                            </div>
                            <span v-if="c.unread > 0" class="msg-unread">{{ c.unread }}</span>
                        </Link>
                    </template>
                    <div v-else class="msg-empty-list">
                        <i class="bi bi-inbox"></i>
                        <p>Henüz mesajın yok.</p>
                    </div>
                </div>
            </aside>

            <section class="msg-chat" :class="{ 'is-empty': !active }">
                <template v-if="active">
                    <div class="msg-chat-head">
                        <Link :href="index_url" class="msg-back" data-testid="messages-back"><i class="bi bi-arrow-left"></i></Link>
                        <div class="msg-avatar-wrap">
                            <img class="msg-avatar" :src="active.peer_avatar" :alt="active.peer_name">
                            <span v-if="active.peer_online" class="msg-online-dot" data-testid="chat-online-dot"></span>
                        </div>
                        <div>
                            <Link :href="active.profile_url" class="msg-chat-name">{{ active.peer_name }}</Link>
                            <div class="msg-chat-sub" data-testid="chat-peer-status">
                                <template v-if="active.peer_online"><span class="msg-status-dot"></span> Çevrimiçi</template>
                                <template v-else-if="active.peer_last_seen">{{ active.peer_last_seen }} aktifti</template>
                                <template v-else>{{ '@' + active.peer_username }}</template>
                            </div>
                        </div>
                        <button type="button" class="msg-sound-toggle ms-auto" @click="toggleSound"
                                data-testid="message-sound-toggle"
                                :title="soundOn ? 'Bildirim sesi açık' : 'Bildirim sesi kapalı'">
                            <i class="bi" :class="soundOn ? 'bi-volume-up' : 'bi-volume-mute'"></i>
                        </button>
                    </div>

                    <div class="msg-thread" ref="thread">
                        <div
                            v-for="m in items"
                            :key="m.id"
                            class="msg-bubble"
                            :class="m.mine ? 'mine' : 'theirs'"
                            :data-mid="m.id"
                        >
                            <div class="msg-bubble-body">{{ m.body }}</div>
                            <div class="msg-bubble-time">
                                {{ m.time }}
                                <span v-if="m.mine" class="msg-receipt" :class="{ read: m.read }"
                                      :data-testid="'msg-receipt-' + m.id"
                                      :title="m.read ? 'Görüldü' : 'Gönderildi'">
                                    <i class="bi" :class="m.read ? 'bi-check2-all' : 'bi-check2'"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <form class="msg-compose" data-testid="message-form" @submit.prevent="send">
                        <input
                            type="text"
                            v-model="input"
                            autocomplete="off"
                            placeholder="Bir mesaj yaz..."
                            maxlength="2000"
                            data-testid="message-input"
                            required
                        >
                        <button type="submit" class="msg-send" data-testid="message-send">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </template>

                <div v-else class="msg-placeholder">
                    <div class="msg-placeholder-icon"><i class="bi bi-chat-square-text"></i></div>
                    <div class="msg-placeholder-title">Bir sohbet seç</div>
                    <div class="msg-placeholder-sub">Soldan bir konuşma seçerek mesajlaşmaya başla.</div>
                </div>
            </section>

        </div>
    </div>
</template>
