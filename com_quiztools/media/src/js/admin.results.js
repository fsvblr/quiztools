(function () {
    document.addEventListener('DOMContentLoaded', () => {
        const from = document.querySelector('#filter_from-lbl')
        if (from) {
            const fromParent = from.closest('span')
            if (fromParent) {
                fromParent.classList.remove('visually-hidden')
                fromParent.style.position = 'absolute'
                fromParent.style.top = '-9px'
                fromParent.style.left = '16px'
                fromParent.style.zIndex = '1'

                fromFilter = fromParent.closest('.js-stools-field-filter')
                if (fromFilter) {
                    fromFilter.style.position = 'relative'
                }
            }
        }

        const to = document.querySelector('#filter_to-lbl')
        if (to) {
            const toParent = to.closest('span')
            if (toParent) {
                toParent.classList.remove('visually-hidden')
                toParent.style.position = 'absolute'
                toParent.style.top = '-9px'
                toParent.style.left = '16px'
                toParent.style.zIndex = '1'

                toFilter = toParent.closest('.js-stools-field-filter')
                if (toFilter) {
                    toFilter.style.position = 'relative'
                }
            }
        }
    });
})()