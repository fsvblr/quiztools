<template>
    <div id="quiz-count-questions" class="quiz-count-questions">
        {{ Joomla.Text._('COM_QUIZTOOLS_QUIZ_COMPONENT_COUNT_QUESTIONS_TEXT_QUESTION') }}
        <span id="quiz-number-current">{{ numberCurrentQuestion }}</span>
        {{ Joomla.Text._('COM_QUIZTOOLS_QUIZ_COMPONENT_COUNT_QUESTIONS_TEXT_OF') }}
        <span id="quiz-number-total">{{ totalQuestions }}</span>
    </div>
</template>

<script setup>
import { inject } from 'vue'

const props = defineProps({
    numberCurrentQuestion: { type: Number, default: 0 },
    totalQuestions: { type: Number, default: 0 },
})

const Joomla = inject('Joomla')
</script>
