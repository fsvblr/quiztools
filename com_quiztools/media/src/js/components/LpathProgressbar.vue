<template>
    <div
        class="lpath-progressbar"
        role="progressbar"
        :aria-valuemin="0"
        :aria-valuemax="total"
        :aria-valuenow="passed"
        :aria-valuetext="`${percent}%`"
    >
        <div class="lpath-progressbar__track">
            <div
                class="lpath-progressbar__fill"
                :style="fillStyle"
            ></div>
        </div>
    </div>
</template>

<script setup>
import { inject, ref, computed } from 'vue'

const countStepsTotal = inject('countStepsTotal', ref(0))
const countStepsPassed = inject('countStepsPassed', ref(0))

const transitionMs = 500
const easing = 'cubic-bezier(0.22, 1, 0.36, 1)'

const total = computed(() => {
    const t = Number(countStepsTotal?.value ?? 0)
    return t > 0 ? t : 0
})

const passed = computed(() => {
    const p = Number(countStepsPassed?.value ?? 0)
    if (p < 0) return 0
    return total.value > 0 ? Math.min(p, total.value) : 0
})

const percent = computed(() => {
    if (total.value === 0) return 0
    return Math.round((passed.value / total.value) * 100)
})

const fillStyle = computed(() => ({
    width: `${percent.value}%`,
    transition: `width ${transitionMs}ms ${easing}`
}))
</script>
