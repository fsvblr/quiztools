(function () {

    document.addEventListener('DOMContentLoaded', () => {

        // Clicking on the certificate preview (to open a modal preview window) selects a row. Let's deselect this.
        document.addEventListener('joomla-dialog:open', (event) => {
            if (event.target && event.target.classList.contains('preview-certificate-dialog')) {
                const parentRow = event.target.JoomlaDialogTrigger.closest('tr')
                if (parentRow) {
                    const checkbox = parentRow.querySelector('input.form-check-input')
                    if (checkbox && checkbox.checked) {
                        checkbox.click()
                    }
                }
            }
        })

    })

})()
