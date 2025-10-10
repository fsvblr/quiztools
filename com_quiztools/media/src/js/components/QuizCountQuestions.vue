<template>
    <div id="quiz-count-questions" class="quiz-count-questions">
        {{ Joomla.Text._('COM_QUIZTOOLS_QUIZ_COMPONENT_COUNT_QUESTIONS_TEXT_QUESTION') }}
        <span id="quiz-number-current">{{ this.numberCurrentQuestion }}</span>
        {{ Joomla.Text._('COM_QUIZTOOLS_QUIZ_COMPONENT_COUNT_QUESTIONS_TEXT_OF') }}
        <span id="quiz-number-total">{{ this.totalQuestions }}</span>
    </div>
</template>

<script>
export default {
    props: {
        numberCurrentQuestion: {
            type: Number,
            default: 0,
        },
        totalQuestions: {
            type: Number,
            default: 0,
        },
    },
    inject: ['Joomla'],
}
</script>
