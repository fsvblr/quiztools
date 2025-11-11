<template>
    <div class="lpath-steps-wrap">
        <LpathProgressbar v-if="showProgressbar && steps" />

        <h4>{{ Joomla.Text._('COM_QUIZTOOLS_LPATH_TITLE_COURSE_STRUCTURE') }}</h4>

        <div id="lpath-steps" class="lpath-steps">
            <div
                v-for="step in steps"
                :key="step.uniqueId + '-' + step.passed + '-' + step.canStart"
                class="lpath-step"
                :class="(step.passed) ? 'passed' : ''"
            >
                <h6 class="step-title">{{ step.title }}</h6>
                <div class="step-desc" v-if="step.desc !== ''" v-html="step.desc"></div>
                <div class="btn-step">
                    <LpathButtonAction
                        v-if="step.canStart"
                        type="step"
                        :step="step"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue'
import LpathProgressbar from './LpathProgressbar.vue'
import LpathButtonAction from './LpathButtonAction.vue'

const props = defineProps({
    steps: {
        type: Array,
        default: () => []
    }
})

const Joomla = inject('Joomla')
const showProgressbar = inject('showProgressbar')

</script>
