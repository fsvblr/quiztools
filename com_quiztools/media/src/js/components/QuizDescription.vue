<template>
    <div id="quiz-description" class="quiz-description" v-html="this.description"></div>
</template>

<script>
export default {
    props: {
        description: {
            type: String,
            default: ''
        },
    },
}
</script>
