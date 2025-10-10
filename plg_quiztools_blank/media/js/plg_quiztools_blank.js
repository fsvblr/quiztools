window.Quiztools = window.Quiztools || {};

(function() {
    Quiztools.blank = {}

    Quiztools.blank.validateAnswer = (id) => {
        let valid = false,
            answerInput = document.querySelector('#questionAnswer' + id),
            blanks = answerInput.closest('.quiz-question').querySelectorAll('.quiztools-blank.drop-target'),
            answer = {
                'type': 'blank',
                'answer': {},
            }

        if (blanks.length > 0) {
            let countFilledBlanks = 0

            blanks.forEach(blank => {
                const id = blank.getAttribute('data-id');
                const filler = blank.querySelector('.filler')
                if (filler) {
                    answer.answer[id] = filler.innerText
                    countFilledBlanks++
                }
            })

            if (countFilledBlanks === blanks.length) {
                valid = true
            }
        }

        if (valid) {
            answerInput.value = JSON.stringify(answer)
        }

        return valid
    }

    document.addEventListener('DOMContentLoaded', () => {

        document.getElementById('quiz-app').addEventListener('dragover', (e) => {
            // {blankX} :
            if (['quiztools-blank', 'drop-target'].every(cls => e.target.classList.contains(cls))) {
                e.preventDefault()
                e.dataTransfer.dropEffect = 'move'
            }

            // Bank of fillers :
            if (['quiztools-blank', 'draggable-zone'].every(cls => e.target.classList.contains(cls))) {
                e.preventDefault()
                e.dataTransfer.dropEffect = 'move'
            }
        })

        document.getElementById('quiz-app').addEventListener('drop', (e) => {
            // {blankX} :
            if (['quiztools-blank', 'drop-target'].every(cls => e.target.classList.contains(cls))) {
                e.preventDefault()
                const id = e.dataTransfer.getData('id')
                const draggedElement = document.getElementById(id)
                if (draggedElement) {
                    // Check: if there is already any child element - cancel drop
                    if (e.target.classList.contains('drop-target')) {
                        e.target.appendChild(draggedElement)
                    } else {
                        // Return to the original location
                        const originalParentId = draggedElement.getAttribute('data-parent-id')
                        const originalParent = document.getElementById(originalParentId)
                        if (originalParent) {
                            originalParent.appendChild(draggedElement)
                        }
                    }
                }
            }

            // Bank of fillers :
            if (['quiztools-blank', 'draggable-zone'].every(cls => e.target.classList.contains(cls))) {
                e.preventDefault()
                const id = e.dataTransfer.getData('id')
                const draggedElement = document.getElementById(id)
                if (draggedElement) {
                    e.target.appendChild(draggedElement)
                }
            }
        })

        document.getElementById('quiz-app').addEventListener('dragstart', (e) => {
            // Fillers :
            if (['quiztools-blank', 'filler'].every(cls => e.target.classList.contains(cls))) {
                e.dataTransfer.setData('id', e.target.id)
            }
        })

    })
})()
