(function () {
    document.addEventListener('DOMContentLoaded', () => {
        const from = document.getElementById('filter_from')
        if (from) {
            from.setAttribute('placeholder', Joomla.Text._('COM_QUIZTOOLS_RESULTS_FILTER_PLACEHOLDER_FROM'))
        }

        const to = document.getElementById('filter_to')
        if (to) {
            to.setAttribute('placeholder', Joomla.Text._('COM_QUIZTOOLS_RESULTS_FILTER_PLACEHOLDER_TO'))
        }
    });
})()
