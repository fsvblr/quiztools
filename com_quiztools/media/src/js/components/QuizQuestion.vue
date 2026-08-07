<template>
    <div id="quiz-questions" class="quiz-questions">

        <div
            v-for="question in questions"
            :key="question.id"
            class="quiz-question"
            :class="{ 'quiz-question--blocked': question.isBlocked }"
            @click.capture="preventInteraction($event, question)"
            @change.capture="preventInteraction($event, question)"
            @input.capture="preventInteraction($event, question)"
            @keydown.capture="preventInteraction($event, question)"
        >
            <div
                class="quiz-question__text"
                :class="{ 'quiz-question__text--disabled': question.isBlocked }"
                v-html="question.text"
            ></div>
            <fieldset
                name="quiz_question_options"
                class="quiz-question__options"
                v-html="question.options"
                :id="'quiz-question-options-' + question.id"
                :disabled="question.isBlocked"
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

const preventInteraction = (event, question) => {
    if (question.isBlocked) {
        event.preventDefault()
        event.stopPropagation()
        event.stopImmediatePropagation()
    }
}
</script>
