import { ref } from 'vue';

// Tek bir global saat (unix saniye) — tüm geri sayımlar bunu paylaşır
const now = ref(Math.floor(Date.now() / 1000));
setInterval(() => { now.value = Math.floor(Date.now() / 1000); }, 1000);

export function useClock() {
    return now;
}

export function formatCountdown(endsTs, nowTs) {
    const diff = endsTs - nowTs;
    if (diff <= 0) return { text: 'Bitti', critical: true };
    const d = Math.floor(diff / 86400);
    const h = Math.floor((diff % 86400) / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = diff % 60;
    let text;
    if (d > 0) text = h > 0 ? `${d} gün ${h} saat` : `${d} gün`;
    else if (h > 0) text = m > 0 ? `${h} saat ${m} dk` : `${h} saat`;
    else if (m > 0) text = s > 0 ? `${m} dk ${String(s).padStart(2, '0')} sn` : `${m} dk`;
    else text = `${s} sn`;
    return { text, critical: diff < 1800 };
}
