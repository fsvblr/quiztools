<template>
    <button
        class="lpath-action btn"
        :class="type"
        :disabled="isLoading"
        @click="handleClick"
    >
        {{ buttons[type] }}
    </button>
</template>

<script setup>
import { inject, ref, onMounted } from 'vue'

const props = defineProps({
    type: {
        type: String,
        default: 'steps'
    },
    step: {
        type: Object,
        default: null
    }
})

const buttons = ref({
    steps: 'To the list of steps',
    step: 'Start',
    next: 'Next: ',
})

onMounted(() => {
    buttons.value = {
        steps: Joomla.Text._('COM_QUIZTOOLS_LPATH_BUTTON_ACTION_STEPS', 'To the list of steps'),
        step: Joomla.Text._('COM_QUIZTOOLS_LPATH_BUTTON_ACTION_STEP', 'Start'),
        next: Joomla.Text._('COM_QUIZTOOLS_LPATH_BUTTON_ACTION_NEXT', 'Next: ') + props.step?.title,
    }
})

const Joomla = inject('Joomla')
const isLoading = inject('isLoading', ref(false))
const clickButtonAction = inject('clickButtonAction', null)

const handleClick = () => {
    if (clickButtonAction) {
        clickButtonAction({ type: props.type, step: props.step })
    }
}
</script>
