<template>
    <div id="quiz-timer" class="quiz-timer" v-html="this.displayedTime"></div>
</template>

<script>
export default {
    data() {
        return {
            displayedTime: '00:00:00',
            timer: null,
        }
    },
    props: {
        timerStyle: {      // Timer style:  0 - Standard, 1 - Remaining time, 2 - Standard with limit
            type: Number,
            default: 0,
        },
        limitTime: {       // Time limit, in minutes
            type: Number,
            default: 0,
        },
        sumTimeSpent: {   // in seconds
            type: Number,
            default: 0,
        },
    },
    inject: ['Joomla'],
    methods: {
        calculationTimePeriods(time) {
            let s = (time % 60).toString()
            let m = Math.floor(time / 60 % 60).toString()
            let h = Math.floor(time / 60 / 60 % 60).toString()
            return {'h': h, 'm': m, 's': s}
        },
        getTimeFormat(time) {
            let timePeriods = this.calculationTimePeriods(time)
            return `${timePeriods.h.padStart(2,'0')}:${timePeriods.m.padStart(2,'0')}:${timePeriods.s.padStart(2,'0')}`
        },
        getTimeFormatWithLimit(time) {
            let timeString = this.getTimeFormat(time),
                limitString = this.getTimeFormat(parseInt(this.limitTime) * 60)
            return timeString +
                '<span class="quiz-timer__limit">' +
                Joomla.Text._('COM_QUIZTOOLS_QUIZ_COMPONENT_TIMER_TEXT_OF') +
                limitString +
                '</span>'
        },
        changeTime() {
            let timeLeft = parseInt(this.limitTime) * 60 - parseInt(this.sumTimeSpent)

            if (timeLeft < 0) {
                clearInterval(this.timer)
                this.timer = null
                Joomla.renderMessages({'warning': [Joomla.Text._('COM_QUIZTOOLS_QUIZ_ERROR_QUIZ_TIME_UP')]})
                this.$emit('update-action', 'finish')
                return
            }

            if (this.timerStyle === 0) {         // 'Standard'
                this.displayedTime = this.getTimeFormat(this.sumTimeSpent)
            } else if (this.timerStyle === 1) {  // Remaining time
                this.displayedTime = this.getTimeFormat(timeLeft)
            } else if (this.timerStyle === 2) {  // 'Standard with limit'
                this.displayedTime = this.getTimeFormatWithLimit(this.sumTimeSpent)
            }

            this.$emit('update-sumTimeSpent', (this.sumTimeSpent + 1))
        },
    },
    mounted() {
        if (!this.timer) {
            this.timer = setInterval(this.changeTime, 1000)
        }
    },
}
</script>
