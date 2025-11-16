<template>
    <div id="quiz-app" class="quiz-app">
        <div v-if="action !== 'result'">
            <div class="quiz-header">
                <div
                    class="quiz-header__data"
                    v-if="started && (quiz.question_number === 1 || quiz.question_points === 1 || quiz.timer_show === 1)"
                >
                    <QuizCountQuestions
                        v-if="quiz.question_number === 1 && quiz.questions_on_page === 0"
                        :numberCurrentQuestion="numberCurrentQuestion"
                        :totalQuestions="totalQuestions"
                    />
                    <QuizAnswerPoints
                        v-if="quiz.question_points === 1 && quiz.questions_on_page === 0 && questions[0]?.type !== 'boilerplate'"
                        :pointsCurrentQuestion="pointsCurrentQuestion"
                    />
                    <!-- Allow users to continue unfinished quiz == No -->
                    <QuizTimer
                        v-if="quiz.allow_continue === 0 && quiz.timer_show === 1 && quiz.limit_time > 0"
                        :timerStyle="quiz.timer_style"
                        :limitTime="quiz.limit_time"
                        :sumTimeSpent="sumTimeSpent"
                        @update-sumTimeSpent="updateSumTimeSpent"
                        :action="action"
                        @update-action="updateAction"
                    />
                </div>
            </div>

            <div class="quiz-main">
                <Preloader :isLoading="isLoading" />
                <form @submit.prevent action="#" name="quiz_form" id="quizForm" class="form-validate">
                    <QuizDescription v-if="quiz.quiz_autostart === 0 && !started" :description="quiz.description" />
                    <QuizQuestion v-if="started" :questions="questions" :questionsFeedback="questionsFeedback" :showFeedback="showFeedback" />

                    <input type="hidden" name="quiz[id]" v-model="quiz.id" />
                    <input type="hidden" name="quiz[resultQuizId]" v-model="resultQuizId" />
                    <input type="hidden" name="quiz[uniqueId]" v-model="uniqueId" />
                    <input type="hidden" name="quiz[action]" v-model="action" />
                    <input type="hidden" name="quiz[lp]" :value="JSON.stringify(lp)" />
                </form>
            </div>

            <div class="quiz-actions">
                <QuizButtonAction
                    v-if="started && !showFeedback && quiz.questions_on_page === 0 && quiz.skip_questions > 0"
                    action="skip"
                    :disabled="isLoading"
                    @click="clickButtonAction('skip')"
                />
                <QuizButtonAction
                    v-if="started && !showFeedback && (quiz.questions_on_page === 0 && quiz.enable_prev_button === 1) && !isFirstQuestion"
                    action="prev"
                    :disabled="isLoading"
                    @click="clickButtonAction('prev')"
                />
                <QuizButtonAction
                    v-if="started && !showFeedback && quiz.questions_on_page === 0    // One question per page (default)
                        && (
                            (quiz.skip_questions === 0 && !isLastQuestion)
                            || (quiz.skip_questions === 1
                                    && (!isLastQuestion
                                        || unansweredQuestionsIds.length > 1
                                        || (unansweredQuestionsIds.length === 1 && parseInt(unansweredQuestionsIds[0]) !== questions[0].id)
                                    )
                                )
                            || (quiz.skip_questions === 2 && !isLastQuestion)
                        )"
                    action="next"
                    :disabled="isLoading"
                    @click="clickButtonAction('next')"
                />
                <QuizButtonAction
                    v-if="started && !showFeedback
                        && (
                            (
                                quiz.questions_on_page === 0
                                && (
                                    unansweredQuestionsIds.length === 0
                                    || (unansweredQuestionsIds.length === 1 && parseInt(unansweredQuestionsIds[0]) === questions[0].id)
                                    || quiz.skip_questions === 2
                                )
                            )
                            || quiz.questions_on_page === 1
                        )"
                    action="finish"
                    :disabled="isLoading"
                    @click="clickButtonAction('finish')"
                />
                <QuizButtonAction
                    v-if="started && showFeedback"
                    :action="nextData.task !== 'result' ? 'continue' : 'quit'"
                    :disabled="isLoading"
                    @click="clickButtonAction('continue')"
                />
                <QuizButtonAction
                    v-if="!started"
                    action="start"
                    :disabled="isLoading"
                    @click="clickButtonAction('start')"
                />
            </div>
        </div>
        <QuizResult v-if="action === 'result'" :resultQuizId="resultQuizId" :isLP="isLP" />
    </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import axios from 'axios'
import QuizCountQuestions from './QuizCountQuestions.vue'
import QuizAnswerPoints from './QuizAnswerPoints.vue'
import QuizTimer from './QuizTimer.vue'
import QuizDescription from './QuizDescription.vue'
import QuizQuestion from './QuizQuestion.vue'
import QuizButtonAction from './QuizButtonAction.vue'
import QuizResult from './QuizResult.vue'
import Preloader from "./Preloader.vue"

const quiz = reactive({})
const resultQuizId = ref(0)
const uniqueId = ref('')
const token = ref('')
const action = ref('')
const started = ref(false)
const isLoading = ref(false)
const form = ref(null)
const sumTimeSpent = ref(0)

const questions = ref([])
const questionsFeedback = ref({})
const showFeedback = ref(false)
const nextData = ref({})

const firstQuestionId = ref(0)
const isFirstQuestion = ref(true)
const lastQuestionId = ref(0)
const isLastQuestion = ref(false)
const numberCurrentQuestion = ref(1)
const totalQuestions = ref(1)
const pointsCurrentQuestion = ref(0)
const unansweredQuestionsIds = ref([])  // !!! array elements are strings

// Learning Path
const isLP = ref(false)
const lp = reactive({})

function clickButtonAction(act) {
    Joomla.removeMessages()
    if (lp && lp.nextStep) {
        lp.nextStep = null
    }
    action.value = act

    if (['next', 'prev', 'finish'].includes(act)) {
        if (!(act === 'finish' && quiz.skip_questions === 2)) {
            const validation = validateForm()
            if (!validation.result) {
                Joomla.renderMessages({ warning: [validation.message] })
                return
            }
        }
    } else if (act === 'continue') {
        actionContinue()
        return
    }

    isLoading.value = true

    setTimeout(async () => {
        const formData = new FormData(form.value)
        formData.append(token.value, 1)
        try {
            const response = await axios.post('/index.php?option=com_quiztools&task=ajaxQuiz.getQuizData', formData)
            if (response.data.success === true) {
                processingResponse(response.data.data)
            } else if (response.data.message) {
                Joomla.renderMessages({ warning: [response.data.message] })
            }
        } catch (error) {
            Joomla.renderMessages({ error: [error.message] })
        } finally {
            isLoading.value = false
            started.value = true
        }
    }, 50)
}

function processingResponse(data) {
    nextData.value = {}
    questionsFeedback.value = {}
    showFeedback.value = false

    // 'lp.nextStep' must be set before 'action.value' is set
    if (data.lpNextStepData?.nextStep) {
        lp.nextStep = data.lpNextStepData.nextStep
    }

    // If there is feedback in the answer:
    if (data.questionsFeedback) {
        questionsFeedback.value = data.questionsFeedback
        showFeedback.value = true
        delete data.questionsFeedback
        nextData.value = data
        return
    }

    if (data.task) {
        if (data.task === 'timeIsUp') {
            Joomla.renderMessages({ warning: [Joomla.Text._('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_TIME_UP')] })
            action.value = 'result'
            return
        }
        action.value = data.task
    }

    if (data.firstQuestionId) firstQuestionId.value = parseInt(data.firstQuestionId)   //task 'start'
    if (data.lastQuestionId) lastQuestionId.value = parseInt(data.lastQuestionId)      //task 'start'

    if (quiz.questions_on_page !== 1 && data.currentQuestionId) {   // 1 => All questions on one page
        isFirstQuestion.value = parseInt(data.currentQuestionId) === parseInt(firstQuestionId.value)
        isLastQuestion.value = parseInt(data.currentQuestionId) === parseInt(lastQuestionId.value)
    }

    if (data.resultQuizId) resultQuizId.value = parseInt(data.resultQuizId)
    if (data.uniqueId) uniqueId.value = data.uniqueId
    if (data.questionsCountTotal) totalQuestions.value = parseInt(data.questionsCountTotal)
    if (data.numberCurrentQuestion) numberCurrentQuestion.value = parseInt(data.numberCurrentQuestion)
    if (data.sumTimeSpent) sumTimeSpent.value = data.sumTimeSpent
    if (data.unansweredQuestionsIds) unansweredQuestionsIds.value = data.unansweredQuestionsIds

    if (data.questions) {
        questions.value = data.questions
        let points = 0
        questions.value.forEach((question) => {
            if (question.points) points += parseFloat(question.points)
            // Does this question(s) have any attempts left?
            if (question.noAttemptsLeft && parseInt(question.noAttemptsLeft) === 1) {
                Joomla.renderMessages({ warning: [Joomla.Text._('COM_QUIZTOOLS_QUIZ_ERROR_QUESTION_NO_ATTEMPTS_LEFT')] })
                action.value = 'result'
            }
        })
        pointsCurrentQuestion.value = points
    }
}

function validateForm() {
    let invalid = false
    let validation = {
        result: true,
        message: '',
    }
    const formData = new FormData(form.value)

    if (!form.value.reportValidity()) invalid = true

    formData.forEach((_, key) => {
        const field = form.value[`${key}`]
        if (field instanceof Node && field.hasAttribute('required') && !field.classList.contains('valid')) {
            invalid = true
        }
    })

    if (questions.value[0] !== undefined) {
        questions.value.forEach((question) => {
            if (Quiztools[question.type]?.validateAnswer && !Quiztools[question.type].validateAnswer(question.id)) {
                invalid = true
            }
        })
    }

    if (invalid) {
        validation = {
            result: false,
            message: Joomla.Text._('COM_QUIZTOOLS_QUIZ_ERROR_VALIDATION'),
        }
    }

    return validation
}

function actionContinue() {
    processingResponse(nextData.value)
}
function updateSumTimeSpent(value) {
    sumTimeSpent.value = value
}
function updateAction(a) {
    action.value = a
}

// the URL-query parser supports arbitrary nesting:
function parseNestedParams(queryString) {
    const params = new URLSearchParams(queryString)
    const result = {}
    for (const [key, value] of params.entries()) {
        const keys = key.replace(/\]/g, '')       // removing the closing brackets
            .split(/\[|\]/g)                                     // splitting by opening brackets
        let current = result
        for (let i = 0; i < keys.length; i++) {
            const k = keys[i]
            if (i === keys.length - 1) {
                current[k] = value
            } else {
                if (!(k in current)) {
                    current[k] = {}
                }
                current = current[k]
            }
        }
    }
    return result
}

watch(action, (newVal) => {
    // The quiz has completed within the Learning Path.
    // Transferring data from the iframe (quiz) to the parent window (LP):
    if (newVal === 'result' && isLP.value === true && lp?.nextStep) {
        window.parent.postMessage(
            { quizFinished: true, lpNextStep: JSON.stringify(lp.nextStep) },
            '*'
        )
    }
})

onMounted(() => {
    form.value = document.querySelector('#quizForm')
    token.value = Joomla.getOptions('com_quiztools.token').value
    Object.assign(quiz, Joomla.getOptions('com_quiztools.quiz'))

    if (quiz.quiz_autostart === 1) {   // Yes
        started.value = true
        clickButtonAction('start')
    }

    if (quiz.questions_on_page === 1) {   // All questions on one page
        isFirstQuestion.value = true
        isLastQuestion.value = true
    }

    // URL parameters:
    const parsedUrlParameters = parseNestedParams(window.location.search)

    if (parsedUrlParameters.lp) {    // Quiz inside the Learning Path
        isLP.value = true
        Object.assign(lp, parsedUrlParameters.lp)
    }
})
</script>
