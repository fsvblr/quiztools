<template>
    <div class="quiz-result">
        <Preloader :isLoading="isLoading" />
        <div v-html="resultHtml"></div>
    </div>
</template>

<script setup>
import { ref, watch, inject } from 'vue'
import axios from 'axios'
import Preloader from "./Preloader.vue"

const props = defineProps({
    resultQuizId: { type: Number, required: true, default: 0 },
    isLP: { type: Boolean, default: false },  // Learning Path
})

const Joomla = inject('Joomla')
const resultHtml = ref('')
const isLoading = ref(true)

async function fetchResultData(id) {
    isLoading.value = true

    try {
        const formData = new FormData()
        formData.append('quiz[resultQuizId]', id)
        formData.append('quiz[action]', 'result')
        formData.append('quiz[isLP]', props.isLP)
        formData.append(Joomla.getOptions('com_quiztools.token').value, 1)

        const response = await axios.post('/index.php?option=com_quiztools&task=ajaxQuiz.getQuizData', formData)

        if (response.data.success === true) {
            const rd = response.data.data.redirect
            if (rd && parseInt(rd.redirectAfterFinish) === 1) {
                const delay = parseInt(rd.redirectAfterFinishDelay)
                if (delay > 0) resultHtml.value = response.data.data.html
                setTimeout(() => {
                    window.location.href = rd.redirectAfterFinishLink
                }, delay * 1000)
            } else {
                resultHtml.value = response.data.data.html
            }
        } else {
            Joomla.renderMessages({ warning: [response.data.message] })
        }
    } catch (error) {
        Joomla.renderMessages({ error: [error.message] })
    } finally {
        isLoading.value = false
    }
}

watch(
    () => props.resultQuizId,
    (newVal) => {
        if (newVal > 0) {
            fetchResultData(newVal)
        }
    },
    { immediate: true }
)
</script>
