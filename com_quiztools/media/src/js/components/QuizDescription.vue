<template>
    <div id="quiz-description" class="quiz-description" v-html="description"></div>
</template>

<script setup>
const props = defineProps({
    description: { type: String, default: '' },
})
</script>
