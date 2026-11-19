<template>
    <div class="quiz-result">
        <Preloader :isLoading="isLoading" />
        <div v-html="resultHtml"></div>
    </div>
</template>

<script setup>
import { ref, watch, inject } from 'vue'
import Preloader from "./Preloader.vue"

const props = defineProps({
    quizId: { type: Number, required: true, default: 0 },
    resultQuizId: { type: Number, required: true, default: 0 },
    isLP: { type: Boolean, default: false },  // Learning Path
    lp: { type: Object, default: {} },
})

const Joomla = inject('Joomla')
const resultHtml = ref('')
const isLoading = ref(true)

function fetchResultData(id) {
    isLoading.value = true

    try {
        const formData = new FormData()
        formData.append('quiz[id]', props.quizId)
        formData.append('quiz[resultQuizId]', id)
        formData.append('quiz[action]', 'result')
        formData.append('quiz[isLP]', props.isLP)
        if (props.isLP === true) {
            formData.append('quiz[lp]', JSON.stringify(props.lp))
        }
        formData.append(Joomla.getOptions('com_quiztools.token').value, 1)

        const orderId = Joomla.getOptions('com_quiztools.orderId')
        if (parseInt(orderId) > 0) {
            formData.append('quiz[orderId]', orderId)
        }

        const rootFull = Joomla.getOptions('system.paths').rootFull || '/'
        const url = `${rootFull}index.php?option=com_quiztools&task=ajaxQuiz.getQuizData`

        Joomla.request({
            url: url,
            method: 'POST',
            data: formData,
            onSuccess: (response) => {
                try {
                    const result = JSON.parse(response)
                    if (result.success === true) {
                        const rd = result.data.redirect
                        // Redirection is not applied if the quiz is part of a Learning Path
                        if (!props.isLP && rd && parseInt(rd.redirectAfterFinish) === 1) {
                            const delay = parseInt(rd.redirectAfterFinishDelay)
                            if (delay > 0) resultHtml.value = result.data.html
                            setTimeout(() => {
                                window.location.href = rd.redirectAfterFinishLink
                            }, delay * 1000)
                        } else {
                            resultHtml.value = result.data.html
                        }
                    } else if (result.message) {
                        Joomla.renderMessages({ warning: [result.message] })
                    }
                } catch (e) {
                    Joomla.renderMessages({ error: [error.message] })
                } finally {
                    isLoading.value = false
                }
            },
            onError: (xhr) => {
                Joomla.renderMessages({ error: [`Error: ${xhr.status} ${xhr.statusText}`] })
                isLoading.value = false
            }
        })
    } catch (error) {
        Joomla.renderMessages({ error: [error.message] })
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
