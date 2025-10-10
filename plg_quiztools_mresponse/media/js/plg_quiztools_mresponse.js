window.Quiztools = window.Quiztools || {};

(function() {
    Quiztools.mresponse = {}

    Quiztools.mresponse.validateAnswer = (id) => {
        let valid = false,
            answerInput = document.querySelector('#questionAnswer' + id),
            fieldset = answerInput.closest('.quiz-question').querySelector('#quiz-question-options-' + id),
            options = fieldset.elements,
            answer = {
                'type': 'mresponse',
                'answer': [],
            }

        for (let i=0, count=options.length; i<count; i++) {
            if (options[i].checked) {
                valid = true
                answer.answer.push(options[i].value)
            }
        }

        if (valid) {
            answerInput.value = JSON.stringify(answer)
        }

        return valid
    }

})()
