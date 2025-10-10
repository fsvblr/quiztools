<template>
    <div
        class="quiz-question__feedback"
        :class="this.feedback.class"
        v-html="this.feedback.text"
    ></div>
</template>

<script>
export default {
    props: {
        feedback: {
            type: Object,
            default: {
                text: {
                    type: String,
                    default: '',
                },
                class: {
                    type: String,
                    default: '',
                },
            },
        },
    },
}
</script>
