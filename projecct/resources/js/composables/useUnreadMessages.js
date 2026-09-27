import { ref } from 'vue';

// Uygulama genelinde paylaşılan (singleton) okunmamış mesaj sayacı.
// Hem AppLayout (sidebar rozeti) hem mesaj sayfası aynı reaktif değeri kullanır,
// böylece yeni mesaj geldiğinde rozet sayfa yenilenmeden anlık güncellenir.
const unread = ref(0);

function setUnread(n) {
    const v = Number(n);
    unread.value = Number.isFinite(v) && v >= 0 ? v : 0;
}
function increment(n = 1) {
    unread.value = Math.max(0, unread.value + n);
}
function decrement(n = 1) {
    unread.value = Math.max(0, unread.value - n);
}
function reset() {
    unread.value = 0;
}

export function useUnreadMessages() {
    return { unread, setUnread, increment, decrement, reset };
}
