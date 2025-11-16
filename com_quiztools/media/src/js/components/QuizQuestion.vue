<template>
    <div id="quiz-questions" class="quiz-questions">
        <div v-for="question in questions" :key="question.id" class="quiz-question">
            <div class="quiz-question__text" v-html="question.text"></div>
            <fieldset
                name="quiz_question_options"
                class="quiz-question__options"
                v-html="question.options"
                :id="'quiz-question-options-' + question.id"
            ></fieldset>
            <input type="hidden" :name="'quiz[question][' + question.id + '][id]'" :value="question.id" />
            <input
                type="hidden"
                :name="'quiz[question][' + question.id + '][answer]'"
                value=""
                :id="'questionAnswer' + question.id"
            />

            <QuizQuestionFeedback v-if="showFeedback" :feedback="questionsFeedback[question.id]" />
        </div>
    </div>
</template>

<script setup>
import QuizQuestionFeedback from "./QuizQuestionFeedback.vue"

const props = defineProps({
    questions: { type: Object, default: () => ({}) },
    questionsFeedback: { type: Object, default: () => ({}) },
    showFeedback: { type: Boolean, default: false },
})
</script>
