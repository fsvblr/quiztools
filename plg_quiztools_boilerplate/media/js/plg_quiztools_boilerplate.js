window.Quiztools = window.Quiztools || {};

(function() {
    Quiztools.boilerplate = {}

    Quiztools.boilerplate.validateAnswer = (id) => {
        let valid = true,
            answerInput = document.querySelector('#questionAnswer' + id),
            answer = {
                'type': 'boilerplate',
                'answer': 'ok',
            }

        answerInput.value = JSON.stringify(answer)

        return valid
    }

})()
