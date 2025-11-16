<template>
    <div id="quiz-answer-points" class="quiz-answer-points">
        {{ Joomla.Text._('COM_QUIZTOOLS_QUIZ_COMPONENT_ANSWER_POINTS_TEXT_POINTS') }}
        <span>{{ pointsCurrentQuestion ? pointsCurrentQuestion : 0 }}</span>
    </div>
</template>

<script setup>
import { inject } from 'vue'

const props = defineProps({
    pointsCurrentQuestion: { type: Number, default: 0 },
})

const Joomla = inject('Joomla')
</script>
