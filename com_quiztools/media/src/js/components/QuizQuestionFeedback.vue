<template>
    <div
        class="quiz-question__feedback"
        :class="feedback.class"
        v-html="feedback.text"
    ></div>
</template>

<script setup>
const props = defineProps({
    feedback: {
        type: Object,
        default: () => ({
            text: '',
            class: '',
        }),
    },
})
</script>
