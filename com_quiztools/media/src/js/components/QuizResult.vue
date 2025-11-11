<template>
    <div class="quiz-result">
        <Preloader :isLoading />
        <div v-html="this.resultHtml"></div>
    </div>
</template>

<script>
import Preloader from "./Preloader.vue"
import axios from 'axios'

export default {
    components: {
        Preloader,
    },
    props: {
        resultQuizId: {
            type: Number,
            required: true,
            default: 0,
        },
        isLP: {  // Learning Path
            type: Boolean,
            default: false,
        }
    },
    inject: ['Joomla'],
    data() {
        return {
            resultHtml: '',
            isLoading: true,
        }
    },
    watch: {
        resultQuizId: {
            immediate: true,
            handler(newVal) {
                if (newVal > 0) {
                    this.fetchResultData(newVal)
                }
            },
        },
    },
    methods: {
        async fetchResultData(id) {
            this.isLoading = true

            try {
                const formData = new FormData()
                formData.append('quiz[resultQuizId]', id)
                formData.append('quiz[action]', 'result')
                formData.append('quiz[isLP]', this.isLP)
                formData.append(this.Joomla.getOptions('com_quiztools.token').value, 1)

                const response = await axios.post('/index.php?option=com_quiztools&task=ajaxQuiz.getQuizData', formData)

                if (response.data.success === true) {
                    if (response.data.data.redirect && response.data.data.redirect.redirectAfterFinish
                        && parseInt(response.data.data.redirect.redirectAfterFinish) === 1
                    ) {
                        const delay = parseInt(response.data.data.redirect.redirectAfterFinishDelay)
                        if (delay > 0) {
                            this.resultHtml = response.data.data.html
                        }
                        setTimeout(() => {
                            window.location.href = response.data.data.redirect.redirectAfterFinishLink
                        }, delay * 1000)
                    } else {
                        this.resultHtml = response.data.data.html
                    }
                } else {
                    Joomla.renderMessages({'warning': [response.data.message]})
                }
            } catch (error) {
                Joomla.renderMessages({'error': [error.message]})
            } finally {
                this.isLoading = false
            }
        },
    },
}
</script>
