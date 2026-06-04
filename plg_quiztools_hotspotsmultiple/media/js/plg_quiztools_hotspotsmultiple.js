window.Quiztools = window.Quiztools || {};

(function() {
    Quiztools.hotspotsmultiple = {}

    let hotspots = null,
        markers = [],
        blockMarkers = false

    document.addEventListener('DOMContentLoaded', () => {
        hotspots = document.querySelectorAll('.question-option-hotspotsmultiple')
        if (hotspots) {
            hotspots.forEach(hotspot => {
                init(hotspot)
            })
        }

        let hotspotsObserver = new MutationObserver((mutationsList, observer) => {
            updateBlockMarkersStatus()
            for (let mutation of mutationsList) {
                for(let node of mutation.addedNodes) {
                    if (!(node instanceof HTMLElement)) continue
                    for(let hotspot of node.querySelectorAll('.question-option-hotspotsmultiple')) {
                        init(hotspot)
                    }
                }
            }
        })
        hotspotsObserver.observe(document.getElementById('quiz-app'), {
            childList: true,
            subtree: true
        })

        updateBlockMarkersStatus()
    })

    // If there is a feedback to current question, block the setting and reset of markers.
    function updateBlockMarkersStatus() {
        blockMarkers = !!document.querySelector('.quiz-question__feedback')

        const resetBtns = document.getElementById('quiz-app').querySelectorAll('.hotspot-reset-btn')
        if (blockMarkers) {
            resetBtns.forEach(resetBtn => resetBtn.setAttribute('disabled', true))
        } else {
            resetBtns.forEach(resetBtn => resetBtn.removeAttribute('disabled'))
        }
    }

    function init(hotspot) {
        if (!hotspot) return
        const id = hotspot.getAttribute('data-id')
        const markerDiv = hotspot.querySelector('.hotspot-image-wrapper')
        const img = hotspot.querySelector('.hotspot-wrapper img')
        if (!img) return
        let prevAnswer = hotspot.getAttribute('data-prevAnswer')

        img.onload = () => {
            markerDiv.addEventListener('click', handleClick)

            if (prevAnswer) {
                prevAnswer = JSON.parse(prevAnswer)  // => [[33, 29.5], [86.5, 26.25]]
                prevAnswer = prevAnswer.map(([x, y]) => ({ x, y }))  // => [{"x": 33, "y": 29.5}, {"x": 86.5, "y": 26.25}]
                renderMarkers(prevAnswer, id)
                markers[id] = prevAnswer
            }

            const resetBtn = hotspot.querySelector('.hotspot-reset-btn')
            if (resetBtn) {
                resetBtn.addEventListener('click', resetMarkers)
            }
        }
    }

    function handleClick(e) {
        if (blockMarkers) return
        const markerDiv = e.target
        const hotspot = markerDiv.closest('.question-option-hotspotsmultiple')
        const id = hotspot.getAttribute('data-id')
        const countMarkers = parseInt(hotspot.getAttribute('data-countMarkers'))

        const rect = markerDiv.getBoundingClientRect()
        const xPercent = ((e.clientX - rect.left) / rect.width) * 100
        const yPercent = ((e.clientY - rect.top) / rect.height) * 100

        if (!markers[id]) {
            markers[id] = []
        }

        if (markers[id].length >= countMarkers && countMarkers > 0) {
            if (Joomla) {
                Joomla.renderMessages({ warning: [Joomla.Text._('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_SITE_WARNING_MORE_MARKERS')] })
            }
            return
        }

        markers[id].push({ x: xPercent, y: yPercent })
        renderMarkers(markers[id], id)
    }

    function removeMarkers(markerDiv) {
        if (!markerDiv) return
        const oldMarkers = markerDiv.querySelectorAll('.hotspot-marker')
        oldMarkers.forEach(oldMarker => oldMarker.remove())
    }

    function renderMarkers(marks, questionId) {
        if (!questionId) return

        const hotspot = document.getElementById('hotspotsmultiple' + questionId + '-area')
        let checkOrder = hotspot.getAttribute('data-checkOrder')
        checkOrder = checkOrder === '1'

        const markerDiv = hotspot.querySelector('.hotspot-image-wrapper')
        removeMarkers(markerDiv)

        marks.forEach(function (m, idx) {
            const dot = document.createElement('div')
            dot.classList.add('hotspot-marker')
            dot.style.position = 'absolute'
            dot.style.border = '2px solid #fff'
            dot.style.borderRadius = '50%'
            dot.style.backgroundColor = '#ff0000'
            if (checkOrder) {
                dot.textContent = (idx + 1).toString()
                dot.style.width = '20px'
                dot.style.height = '20px'
                dot.style.color = '#fff'
                dot.style.display = 'flex'
                dot.style.alignItems = 'center'
                dot.style.justifyContent = 'center'
                dot.style.fontSize = '12px'
                dot.style.fontWeight = 'bold'
            } else {
                dot.style.width = '12px'
                dot.style.height = '12px'
            }
            dot.style.left = m.x + '%'
            dot.style.top = m.y + '%'
            dot.style.transform = 'translate(-50%, -50%)'
            dot.style.pointerEvents = 'none'
            markerDiv.appendChild(dot)
        })
    }

    function resetMarkers(e) {
        if (blockMarkers) return
        if (Joomla) Joomla.removeMessages()
        const btn = e.target
        const hotspot = btn.closest('.question-option-hotspotsmultiple')
        const id = hotspot.getAttribute('data-id')
        markers[id] = []
        const markerDiv = hotspot.querySelector('.hotspot-image-wrapper')
        removeMarkers(markerDiv)
    }

    window.addEventListener('resize', () => {
        setTimeout(hotspotsRedrawing, 100)
    })

    window.addEventListener('orientationchange', () => {
        setTimeout(hotspotsRedrawing, 100)
    })

    function hotspotsRedrawing() {
        hotspots = document.querySelectorAll('.question-option-hotspotsmultiple')
        if (hotspots) {
            hotspots.forEach(hotspot => {
                const id = hotspot.getAttribute('data-id')
                const markerDiv = hotspot.querySelector('.hotspot-image-wrapper')
                removeMarkers(markerDiv)
                if (markers[id]) {
                    renderMarkers(markers[id], id)
                }
            })
        }
    }

    Quiztools.hotspotsmultiple.validateAnswer = (id) => {
        let valid = false,
            answerInput = document.querySelector('#questionAnswer' + id),
            answer = {
                'type': 'hotspotsmultiple',
                'answer': [],
            }

        if (!markers[id]) {
            return valid
        }

        let countMarkers
        const hotspot = document.getElementById('hotspotsmultiple' + id + '-area')
        if (hotspot) {
            countMarkers = hotspot.getAttribute('data-countMarkers')
        }

        if (markers[id].length !== parseInt(countMarkers)) {
            return valid
        }

        valid = true
        answer.answer = markers[id]
        answerInput.value = JSON.stringify(answer)

        return valid
    }

})()
