<script setup>
defineProps({
    size: { type: String, default: '40px' },
    lines: { type: Number, default: 2 },
});
</script>

<template>
    <div class="skel-avatar-row" aria-hidden="true">
        <span class="skeleton skel-avatar-circle" :style="{ width: size, height: size }"></span>
        <div class="skel-avatar-lines">
            <span
                v-for="i in lines"
                :key="i"
                class="skeleton skel-avatar-line"
                :style="{ width: i === 1 ? '60%' : '40%' }"
            ></span>
        </div>
    </div>
</template>

<style scoped>
.skel-avatar-row { display: flex; align-items: center; gap: 12px; }
.skel-avatar-circle { border-radius: 50%; flex-shrink: 0; }
.skel-avatar-lines { display: flex; flex-direction: column; gap: 7px; flex: 1; }
.skel-avatar-line { height: 10px; border-radius: 6px; display: block; }
</style>
