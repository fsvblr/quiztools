<template>
    <div id="lpath-app" class="lpath-app">
        <Preloader :isLoading="isLoading" />

        <div
            v-if="action === 'steps' && lpath.description"
            class="lpath-desc"
            v-html="lpath.description"
        ></div>

        <LpathSteps
            v-if="action === 'steps'"
            :steps="lpath.steps"
        />

        <LpathStep
            v-if="action === 'step'"
            :step="step"
            @loaded="handleIframeLoaded"
        />
    </div>
</template>

<script setup>
import { ref, provide, onMounted, onUnmounted, nextTick } from 'vue'
import LpathSteps from './LpathSteps.vue'
import LpathStep from './LpathStep.vue'
import Preloader from './Preloader.vue'
import axios from 'axios'

const lpath = ref({})
const countStepsTotal = ref(0)
const countStepsPassed = ref(0)
const showProgressbar = ref(false)
const step = ref({})
const nextStep = ref({})
const action = ref('steps')
const token = ref('')
const isLoading = ref(false)

const sendRequest = async (actionType, stepData = step.value, stepStage = null) => {
    const formData = new FormData()
    formData.append('lpath[id]', lpath.value.id)
    formData.append('lpath[action]', actionType)
    formData.append('lpath[step]', JSON.stringify(stepData))
    formData.append(token.value, 1)

    if (actionType === 'markArticle' && stepStage) {
        formData.append('lpath[stepStage]', stepStage)
    }

    try {
        const response = await axios.post(
            '/index.php?option=com_quiztools&task=ajaxLpath.getLpathData',
            formData
        )
        return response.data
    } catch (error) {
        Joomla.renderMessages({ error: [error.message] })
        return null
    }
}

const clickButtonAction = async ({ type, step: clickedStep, stepStage = null }) => {
    Joomla.removeMessages()
    nextStep.value = {}

    if (type === 'next') {
        type = 'step'
    }

    if (type === 'markArticle') {
        const data = await sendRequest(type, clickedStep || step.value, stepStage)
        if (data?.success) {
            processingResponse(data.data)
        } else if (data?.message) {
            Joomla.renderMessages({ warning: [data.message] })
        }
        return
    }

    action.value = type
    isLoading.value = true

    if (type === 'step') {
        step.value = clickedStep || {}
        return
    }

    await nextTick()

    const data = await sendRequest(type, step.value)
    if (data?.success) {
        processingResponse(data.data)
    } else if (data?.message) {
        Joomla.renderMessages({ warning: [data.message] })
    }

    isLoading.value = false
}

const processingResponse = (data) => {
    if (data.hasOwnProperty('markedType')) {  // after action 'markArticle'
        if (data.nextStep) {
            nextStep.value = data.nextStep
        }
        return
    }

    lpath.value.steps = []
    countStepsTotal.value = 0
    countStepsPassed.value = 0
    step.value = {}
    action.value = 'steps'

    if (data.hasOwnProperty('steps')) {
        lpath.value.steps = data.steps.steps
        countStepsTotal.value = parseInt(data.steps.countStepsTotal)
        countStepsPassed.value = parseInt(data.steps.countStepsPassed)
    }
}

function handleIframeLoaded() {
    isLoading.value = false
}

function handleMessage(event) {
    if (event.data?.quizFinished === true && event.data?.lpNextStep) {
        // Let's give the quiz results some time to load.
        // We won't immediately show the "Next" button on the blank page with preloader.
        setTimeout(() => {
            nextStep.value = JSON.parse(event.data.lpNextStep)
        }, 5000)
    }
}

provide('showProgressbar', showProgressbar)
provide('countStepsTotal', countStepsTotal)
provide('countStepsPassed', countStepsPassed)
provide('isLoading', isLoading)
provide('clickButtonAction', clickButtonAction)
provide('nextStep', nextStep)

onMounted(() => {
    token.value = Joomla.getOptions('com_quiztools.token').value
    lpath.value = Joomla.getOptions('com_quiztools.lpath')
    countStepsTotal.value = parseInt(lpath.value.countStepsTotal)
    countStepsPassed.value = parseInt(lpath.value.countStepsPassed)
    showProgressbar.value = !!Number(lpath.value.showProgressbar)

    // Listening to messages from the children's iframe:
    window.addEventListener('message', handleMessage);

    //console.log(lpath.value)
})

onUnmounted(() => {
    window.removeEventListener('message', handleMessage);
});
</script>
