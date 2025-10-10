<template>
    <div id="quiz-answer-points" class="quiz-answer-points">
        {{ Joomla.Text._('COM_QUIZTOOLS_QUIZ_COMPONENT_ANSWER_POINTS_TEXT_POINTS') }}
        <span>{{ this.pointsCurrentQuestion ? this.pointsCurrentQuestion : 0 }}</span>
    </div>
</template>

<script>
export default {
    props: {
        pointsCurrentQuestion: {
            type: Number,
            default: 0,
        },
    },
    inject: ['Joomla'],
}
</script>
