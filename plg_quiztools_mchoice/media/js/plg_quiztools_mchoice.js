window.Quiztools = window.Quiztools || {};

(function() {
    Quiztools.mchoice = {}

    Quiztools.mchoice.validateAnswer = (id) => {
        let valid = false,
            answerInput = document.querySelector('#questionAnswer' + id),
            fieldset = answerInput.closest('.quiz-question').querySelector('#quiz-question-options-' + id),
            options = fieldset.elements,
            answer = {
                'type': 'mchoice',
                'answer': '',
            }

        for (let i=0, count=options.length; i<count; i++) {
            if (options[i].checked) {
                valid = true
                answer.answer = options[i].value
                answerInput.value = JSON.stringify(answer)
                break
            }
        }

        return valid
    }

})()
