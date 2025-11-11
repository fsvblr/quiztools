<template>
    <div id="quiz-app" class="quiz-app">
        <div v-if="this.action !== 'result'">
            <div class="quiz-header">
                <div class="quiz-header__data" v-if="this.started &&
                    (this.quiz.question_number === 1 || this.quiz.question_points === 1 || this.quiz.timer_show === 1)"
                >
                    <QuizCountQuestions
                        v-if="this.quiz.question_number === 1 && this.quiz.questions_on_page === 0"
                        :numberCurrentQuestion
                        :totalQuestions
                    />
                    <QuizAnswerPoints
                        v-if="this.quiz.question_points === 1 && this.quiz.questions_on_page === 0 && this.questions[0].type !== 'boilerplate'"
                        :pointsCurrentQuestion
                    />
                    <!-- Allow users to continue unfinished quiz == No -->
                    <QuizTimer
                        v-if="this.quiz.allow_continue === 0 && this.quiz.timer_show === 1 && this.quiz.limit_time > 0"
                        :timerStyle="this.quiz.timer_style"
                        :limitTime="this.quiz.limit_time"
                        :sumTimeSpent
                        @update-sumTimeSpent="updateSumTimeSpent"
                        :action
                        @update-action="updateAction"
                    />
                </div>
            </div>

            <div class="quiz-main">
                <Preloader :isLoading />
                <form @submit.prevent action="#" name="quiz_form" id="quizForm" class="form-validate">
                    <QuizDescription v-if="this.quiz.quiz_autostart===0 && !this.started" :description="this.quiz.description" />
                    <QuizQuestion v-if="this.started" :questions :questionsFeedback :showFeedback />

                    <input type="hidden" name="quiz[id]" v-model="this.quiz.id" />
                    <input type="hidden" name="quiz[resultQuizId]" v-model="this.resultQuizId" />
                    <input type="hidden" name="quiz[uniqueId]" v-model="this.uniqueId" />
                    <input type="hidden" name="quiz[action]" v-model="this.action" />
                    <input type="hidden" name="quiz[lp]" :value="JSON.stringify(this.lp)" />
                </form>
            </div>

            <div class="quiz-actions">
                <QuizButtonAction
                    v-if="this.started && !this.showFeedback && this.quiz.questions_on_page === 0 && this.quiz.skip_questions > 0"
                    action="skip"
                    :disabled="this.isLoading"
                    @click="this.clickButtonAction('skip')" />
                <QuizButtonAction
                    v-if="this.started && !this.showFeedback && (this.quiz.questions_on_page === 0 && this.quiz.enable_prev_button === 1) && !this.isFirstQuestion"
                    action="prev"
                    :disabled="this.isLoading"
                    @click="this.clickButtonAction('prev')" />
                <QuizButtonAction
                    v-if="this.started && !this.showFeedback && this.quiz.questions_on_page === 0     // One question per page (default)
                        && (
                            (this.quiz.skip_questions === 0 && !this.isLastQuestion)
                            || (this.quiz.skip_questions === 1
                                    && (!this.isLastQuestion
                                          || this.unansweredQuestionsIds.length > 1
                                            || (this.unansweredQuestionsIds.length === 1 && parseInt(this.unansweredQuestionsIds[0]) !== this.questions[0].id)
                                        )
                                )
                            || (this.quiz.skip_questions === 2 && !this.isLastQuestion)
                        )"
                    action="next"
                    :disabled="this.isLoading"
                    @click="this.clickButtonAction('next')" />
                <QuizButtonAction
                    v-if="this.started && !this.showFeedback
                        && (
                            (
                                this.quiz.questions_on_page === 0
                                    && (
                                        this.unansweredQuestionsIds.length === 0
                                        || (this.unansweredQuestionsIds.length === 1 && parseInt(this.unansweredQuestionsIds[0]) === this.questions[0].id)
                                        || this.quiz.skip_questions === 2
                                    )
                            )
                            || this.quiz.questions_on_page === 1
                        )"
                    action="finish"
                    :disabled="this.isLoading"
                    @click="this.clickButtonAction('finish')" />
                <QuizButtonAction
                    v-if="this.started && this.showFeedback"
                    :action="this.nextData.task !== 'result' ? 'continue' : 'quit'"
                    :disabled="this.isLoading"
                    @click="this.clickButtonAction('continue')" />
                <QuizButtonAction
                    v-if="!this.started"
                    action="start"
                    :disabled="this.isLoading"
                    @click="this.clickButtonAction('start')" />
            </div>
        </div>
        <QuizResult v-if="this.action === 'result'" :resultQuizId :isLP />
    </div>
</template>

<script>
import QuizCountQuestions from './QuizCountQuestions.vue'
import QuizAnswerPoints from './QuizAnswerPoints.vue'
import QuizTimer from './QuizTimer.vue'
import QuizDescription from './QuizDescription.vue'
import QuizQuestion from './QuizQuestion.vue'
import QuizButtonAction from './QuizButtonAction.vue'
import QuizResult from './QuizResult.vue'
import Preloader from "./Preloader.vue"
import axios from 'axios'

export default {
    components: {
        Preloader,
        QuizCountQuestions,
        QuizAnswerPoints,
        QuizTimer,
        QuizDescription,
        QuizQuestion,
        QuizButtonAction,
        QuizResult,
    },
    data() {
        return {
            quiz: {},
            resultQuizId: 0,
            uniqueId: '',
            token: '',
            action: '',
            started: false,
            isLoading: false,
            form: null,
            sumTimeSpent: 0,

            questions: {},
            questionsFeedback: {},
            showFeedback: false,
            nextData: {},

            firstQuestionId: 0,
            isFirstQuestion: true,
            lastQuestionId: 0,
            isLastQuestion: false,
            numberCurrentQuestion: 1,
            totalQuestions: 1,
            pointsCurrentQuestion: 0,
            unansweredQuestionsIds: [],  // !!! array elements are strings

            // Learning Path
            isLP: false,
            lp: {},
        }
    },
    methods: {
        clickButtonAction(action) {
            Joomla.removeMessages()
            if (this.lp && this.lp.nextStep) {
                this.lp.nextStep = null
            }
            this.action = action

            if (['next', 'prev', 'finish'].includes(action)) {
                if (!(action === 'finish' && this.quiz.skip_questions === 2)) {
                    let validation = this.validateForm()
                    if (!validation.result) {
                        Joomla.renderMessages({'warning': [validation.message]})
                        return
                    }
                }
            } else if (action === 'continue') {
                this.actionContinue()
                return
            }

            this.isLoading = true

            setTimeout(async () => {
                let formData = new FormData(this.form)
                formData.append(this.token, 1)
                try {
                    await axios.post('/index.php?option=com_quiztools&task=ajaxQuiz.getQuizData', formData)
                        .then((response) => {
                            if (response.data.success === true) {
                                this.processingResponse(response.data.data)
                            } else {
                                if (response.data.message) {
                                    Joomla.renderMessages({'warning': [response.data.message]})
                                }
                            }
                        })
                        .finally(() => {
                            this.isLoading = false
                            this.started = true
                        })
                } catch (error) {
                    Joomla.renderMessages({'error': [error.message]})
                }
            }, 50)
        },
        processingResponse(data) {
            this.nextData = {}
            this.questionsFeedback = {}
            this.showFeedback = false

            // 'this.lp.nextStep' must be set before 'this.action' is set
            if (data.hasOwnProperty('lpNextStepData')) {
                if (data.lpNextStepData.nextStep) {
                    this.lp.nextStep = data.lpNextStepData.nextStep
                }
            }

            // If there is feedback in the answer:
            if (data.hasOwnProperty('questionsFeedback')) {
                this.questionsFeedback = data.questionsFeedback
                this.showFeedback = true
                delete data.questionsFeedback
                this.nextData = data
                return
            }

            if (data.hasOwnProperty('task')) {
                if (data.task === 'timeIsUp') {
                    Joomla.renderMessages({'warning': [Joomla.Text._('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_TIME_UP')]})
                    this.action = 'result'
                    return
                }
                this.action = data.task
            }
            if (data.hasOwnProperty('firstQuestionId')) {  //task 'start'
                this.firstQuestionId = parseInt(data.firstQuestionId)
            }
            if (data.hasOwnProperty('lastQuestionId')) {  //task 'start'
                this.lastQuestionId = parseInt(data.lastQuestionId)
            }
            if (this.quiz.questions_on_page !== 1) { // 1 => All questions on one page
                if (data.hasOwnProperty('currentQuestionId')) {
                    this.isFirstQuestion = parseInt(data.currentQuestionId) === parseInt(this.firstQuestionId)
                    this.isLastQuestion = parseInt(data.currentQuestionId) === parseInt(this.lastQuestionId)
                }
            }
            if (data.hasOwnProperty('resultQuizId')) {
                this.resultQuizId = parseInt(data.resultQuizId)
            }
            if (data.hasOwnProperty('uniqueId')) {
                this.uniqueId = data.uniqueId
            }
            if (data.hasOwnProperty('questionsCountTotal')) {
                this.totalQuestions = parseInt(data.questionsCountTotal)
            }
            if (data.hasOwnProperty('numberCurrentQuestion')) {
                this.numberCurrentQuestion = parseInt(data.numberCurrentQuestion)
            }
            if (data.hasOwnProperty('sumTimeSpent')) {
                this.sumTimeSpent = data.sumTimeSpent
            }
            if (data.hasOwnProperty('unansweredQuestionsIds')) {
                this.unansweredQuestionsIds = data.unansweredQuestionsIds
            }

            if (data.hasOwnProperty('questions')) {
                this.questions = data.questions
                let pointsCurrentQuestion = 0
                this.questions.forEach(function(question) {
                    if (question.hasOwnProperty('points')) {
                        pointsCurrentQuestion += parseFloat(question.points)
                    }
                    // Does this question(s) have any attempts left?
                    if (question.hasOwnProperty('noAttemptsLeft') && parseInt(question.noAttemptsLeft) === 1) {
                        Joomla.renderMessages({'warning': [Joomla.Text._('COM_QUIZTOOLS_QUIZ_ERROR_QUESTION_NO_ATTEMPTS_LEFT')]})
                        this.action = 'result'
                    }
                });
                this.pointsCurrentQuestion = pointsCurrentQuestion
            }
        },
        validateForm() {
            let invalid = false,
                validation = {
                    'result': true,
                    'message': '',
                },
                formData = new FormData(this.form)

            if (!this.form.reportValidity()) {
                invalid = true
            }

            formData.forEach((value, key) => {
                let field = this.form[`${key}`]
                if (field instanceof Node && field.hasAttribute('required') && !field.classList.contains('valid')) {
                    invalid = true
                }
            })

            if (this.questions[0] !== undefined) {
                this.questions.forEach((question) => {
                    if (Quiztools[question.type].validateAnswer !== undefined && !Quiztools[question.type].validateAnswer(question.id)) {
                        invalid = true
                    }
                })
            }

            if (invalid) {
                validation = {
                    'result': false,
                    'message': Joomla.Text._('COM_QUIZTOOLS_QUIZ_ERROR_VALIDATION'),
                }
            }

            return validation
        },
        actionContinue() {
            let data = this.nextData
            this.processingResponse(data)
        },
        updateSumTimeSpent(value) {
            this.sumTimeSpent = value
        },
        updateAction(action) {
            this.action = action
        },
        // the URL-query parser supports arbitrary nesting:
        parseNestedParams(queryString) {
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
        },
    },
    watch: {
        action(newVal) {
            // The quiz has completed within the Learning Path.
            // Transferring data from the iframe (quiz) to the parent window (LP):
            if (newVal === 'result' && this.isLP === true && this.lp?.nextStep) {
                window.parent.postMessage(
                    { 'quizFinished': true, 'lpNextStep': JSON.stringify(this.lp.nextStep) },
                    '*'
                )
            }
        }
    },
    mounted() {
        this.form = document.querySelector('#quizForm')
        this.token = Joomla.getOptions('com_quiztools.token').value
        this.quiz = Joomla.getOptions('com_quiztools.quiz')

        if (this.quiz.quiz_autostart === 1) { // Yes
            this.started = true;
            this.clickButtonAction('start')
        }

        if (this.quiz.questions_on_page === 1) {  // All questions on one page
            this.isFirstQuestion = true
            this.isLastQuestion = true
        }

        // URL parameters:
        const parsedUrlParameters = this.parseNestedParams(window.location.search);

        if (parsedUrlParameters.lp) {  // Quiz inside the Learning Path
            this.isLP = true
            this.lp = parsedUrlParameters.lp
        }
    },
}
</script>
