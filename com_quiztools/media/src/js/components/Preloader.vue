<template>
    <div v-if="isLoading" class="preloader-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" class="preloader">
            <linearGradient id="a11">
                <stop offset="0" stop-color="#31386B" stop-opacity="0" />
                <stop offset="1" stop-color="#31386B" />
            </linearGradient>
            <circle
                fill="none"
                stroke="url(#a11)"
                stroke-width="18"
                stroke-linecap="round"
                stroke-dasharray="0 44 0 44 0 44 0 44 0 360"
                cx="100"
                cy="100"
                r="70"
                transform-origin="center"
            >
                <animateTransform
                    type="rotate"
                    attributeName="transform"
                    calcMode="discrete"
                    dur="2.5s"
                    values="360;324;288;252;216;180;144;108;72;36"
                    repeatCount="indefinite"
                />
            </circle>
        </svg>
    </div>
</template>

<script setup>
const props = defineProps({
    isLoading: {
        type: Boolean,
        default: false
    }
})
</script>
