<template>
    <div class="lpath-step-wrap">
        <div class="btn-list top">
            <LpathButtonAction
                v-if="showStepsButton"
                type="steps"
                :step="step"
            />
        </div>

        <iframe
            v-if="step && step.link"
            ref="iframeRef"
            :src="step.link"
            id="step-iframe"
            class="animated-iframe"
            :class="{ 'is-visible': isVisible }"
        />

        <div
            v-if="step.type === 'a' || (step.type === 'q' && nextStep && nextStep.canStart && isVisible)"
            class="btn-list bottom"
        >
            <LpathButtonAction
                v-if="nextStep && nextStep.canStart"
                type="next"
                :step="nextStep"
                @click="scrollToTop"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount, nextTick, inject } from 'vue'
import LpathButtonAction from './LpathButtonAction.vue'

const props = defineProps({
    step: {
        type: Object,
        default: () => ({})
    }
})
const emit = defineEmits(['loaded'])

const iframeRef = ref(null)
const isVisible = ref(false) // controls fade-in
const showStepsButton = ref(false)
let observer = null
let timer = null

const clickButtonAction = inject('clickButtonAction')
const nextStep = inject('nextStep')

// Setting the height of an iframe to its contents
function updateHeight() {
    try {
        const iframe = iframeRef.value
        const doc = iframe.contentDocument || iframe.contentWindow.document

        iframe.style.height = 'auto'

        const newHeight = Math.max(
            doc.body.scrollHeight,
            doc.documentElement.scrollHeight
        )

        iframe.style.height = newHeight + 'px'
    } catch (e) {
        //console.warn('Error measuring iframe height:', e)
    }
}

function observeChanges() {
    const iframe = iframeRef.value
    const doc = iframe.contentDocument || iframe.contentWindow.document

    // Primary processing of links
    attachLinkHandler(doc)

    observer = new MutationObserver(() => {
        updateHeight()
        attachLinkHandler(doc)
    })

    observer.observe(doc.body, {
        childList: true,
        subtree: true,
        attributes: true,
        characterData: true
    })
}

// When click on any link inside the iframe, it should open in a separate tab.
// A universal helper for links inside an iframe.
function attachLinkHandler(iframeDoc) {
    function processLink(link) {
        link.addEventListener('click', (e) => {
            e.preventDefault()

            const href = link.getAttribute('href')
            if (!href) return

            try {
                const baseHref = iframeDoc.location.href
                const fullUrl = new URL(href, baseHref).href
                window.open(fullUrl, '_blank')
            } catch (err) {
                //console.warn('Error processing link:', err)
            }
        })
    }

    // Primary processing
    iframeDoc.querySelectorAll('a').forEach(link => processLink(link))

    // Observer for New Links
    const linkObserver = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) {
                    if (node.tagName === 'A') {
                        processLink(node)
                    } else {
                        node.querySelectorAll?.('a').forEach(l => processLink(l))
                    }
                }
            })
        })
    })

    linkObserver.observe(iframeDoc.body, {
        childList: true,
        subtree: true
    })
}

// Smooth scrolling up the page
function scrollToTop() {
    if (window.scrollY > 0) {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    }
}

watch(
    () => props.step,
    async (newStep) => {
        if (!newStep || !newStep.link) {
            if (observer) {
                observer.disconnect()
            }
            isVisible.value = false
            showStepsButton.value = false
            return
        }

        await nextTick()
        const iframe = iframeRef.value
        if (!iframe) return

        iframe.addEventListener(
            'load',
            () => {
                updateHeight()
                observeChanges()
                isVisible.value = true // Enable fade-in
                emit('loaded')   // Informing the parent that the download is complete

                // Show the "To list" button
                showStepsButton.value = true

                // If there's an article:
                if (newStep.type === 'a') {
                    // Immediately mark it as started
                    clickButtonAction({ type: 'markArticle', step: newStep, stepStage: 'start' })
                    // After X seconds mark it as finished
                    let minTime = (newStep.minTime && parseInt(newStep.minTime)) ? parseInt(newStep.minTime) : 10;
                    timer = setTimeout(() => {
                        clickButtonAction({ type: 'markArticle', step: props.step, stepStage: 'finish' })
                    }, minTime * 1000)
                }
            },
            { once: true }
        )

        iframe.addEventListener(
            'error',
            () => {
                // Show "To list" button when loading fails
                showStepsButton.value = true
            },
            { once: true }
        )
    },
    { immediate: true }
)

onBeforeUnmount(() => {
    if (observer) {
        observer.disconnect()
    }

    if (timer) {
        clearTimeout(timer)
    }
})
</script>
