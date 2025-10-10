<template>
    <button class="quiz-action btn" :class="this.action" :disabled="this.disabled">
        {{ this.buttons[this.action] }}
    </button>
</template>

<script>
export default {
    data() {
        return {
            buttons: {
                'start': 'Start',
                'next': 'Next',
                'prev': 'Prev',
                'skip': 'Skip',
                'continue': 'Continue',
                'finish': 'Finish',
                'quit': 'Quit',
            },
        }
    },
    props: {
        action: {
            type: String,
            default: '',
        },
        disabled: {
            type: Boolean,
            default: false,
        }
    },
    mounted() {
        this.buttons = {
            'start': Joomla.Text._('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_START', 'Start'),
            'next': Joomla.Text._('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_NEXT', 'Next'),
            'prev': Joomla.Text._('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_PREV', 'Prev'),
            'skip': Joomla.Text._('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_SKIP', 'Skip'),
            'continue': Joomla.Text._('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_CONTINUE', 'Continue'),
            'finish': Joomla.Text._('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_FINISH', 'Finish'),
            'quit': Joomla.Text._('COM_QUIZTOOLS_QUIZ_BUTTON_ACTION_QUIT', 'Quit'),
        }
    },
}
</script>
