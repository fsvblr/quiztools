<template>
    <div id="quiz-timer" class="quiz-timer" v-html="displayedTime"></div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'

const props = defineProps({
    timerStyle: { type: Number, default: 0 },  // 0 - Standard, 1 - Remaining time, 2 - Standard with limit
    limitTime: { type: Number, default: 0 },   // minutes
    sumTimeSpent: { type: Number, default: 0 } // seconds
})
const emit = defineEmits(['update-sumTimeSpent', 'update-action'])

const Joomla = inject('Joomla')
const displayedTime = ref('00:00:00')
let timer = null

function calculationTimePeriods(time) {
    const s = (time % 60).toString()
    const m = Math.floor((time / 60) % 60).toString()
    const h = Math.floor((time / 60 / 60) % 60).toString()
    return { h, m, s }
}
function getTimeFormat(time) {
    const t = calculationTimePeriods(time)
    return `${t.h.padStart(2,'0')}:${t.m.padStart(2,'0')}:${t.s.padStart(2,'0')}`
}
function getTimeFormatWithLimit(time) {
    const timeString = getTimeFormat(time)
    const limitString = getTimeFormat(parseInt(props.limitTime) * 60)
    return (
        timeString +
        '<span class="quiz-timer__limit">' +
        Joomla.Text._('COM_QUIZTOOLS_QUIZ_COMPONENT_TIMER_TEXT_OF') +
        limitString +
        '</span>'
    )
}
function changeTime() {
    const timeLeft = parseInt(props.limitTime) * 60 - parseInt(props.sumTimeSpent)

    if (timeLeft < 0) {
        clearInterval(timer)
        timer = null
        Joomla.renderMessages({ warning: [Joomla.Text._('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_TIME_UP')] })
        emit('update-action', 'finish')
        return
    }

    if (props.timerStyle === 0) {          // 'Standard'
        displayedTime.value = getTimeFormat(props.sumTimeSpent)
    } else if (props.timerStyle === 1) {   // Remaining time
        displayedTime.value = getTimeFormat(timeLeft)
    } else if (props.timerStyle === 2) {   // 'Standard with limit'
        displayedTime.value = getTimeFormatWithLimit(props.sumTimeSpent)
    }

    emit('update-sumTimeSpent', props.sumTimeSpent + 1)
}

onMounted(() => {
    if (!timer) timer = setInterval(changeTime, 1000)
})
</script>
