<script setup>
// İlan kartı iskeleti: görsel alanı + başlık + meta + alt (fiyat/süre) satırı.
</script>

<template>
    <div class="skel-card" aria-hidden="true" data-testid="skeleton-card">
        <div class="skeleton skel-card-img"></div>
        <div class="skel-card-body">
            <span class="skeleton skel-card-title"></span>
            <span class="skeleton skel-card-title short"></span>
            <div class="skel-card-meta">
                <span class="skeleton skel-chip"></span>
                <span class="skeleton skel-chip"></span>
            </div>
            <div class="skel-card-bottom">
                <span class="skeleton skel-price"></span>
                <span class="skeleton skel-timer"></span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.skel-card {
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    background: var(--card);
    display: flex;
    flex-direction: column;
}
.skel-card-img { width: 100%; aspect-ratio: 4 / 3; display: block; }
.skel-card-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 9px; }
.skel-card-title { height: 13px; width: 90%; border-radius: 6px; display: block; }
.skel-card-title.short { width: 60%; }
.skel-card-meta { display: flex; gap: 8px; margin-top: 2px; }
.skel-chip { height: 10px; width: 58px; border-radius: 6px; display: block; }
.skel-card-bottom { display: flex; justify-content: space-between; margin-top: 8px; padding-top: 10px; border-top: 1px solid var(--border); }
.skel-price { height: 18px; width: 84px; border-radius: 6px; display: block; }
.skel-timer { height: 14px; width: 60px; border-radius: 6px; display: block; }
</style>
