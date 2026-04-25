import JoomlaDialog from 'joomla.dialog';

window.addEventListener('load', function() {
    let btnReactivate = document.querySelector('.button-order-reactivate'),
        reactivateDialog

    if(btnReactivate) {
        btnReactivate.addEventListener('click', function() {
            reactivateDialog = new JoomlaDialog({
                popupType: 'inline',
                src: '#order-reactivate',
                textHeader: Joomla.Text._('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_HEADER'),
                className: 'dialog-order-reactivate',
                popupButtons : [
                    {
                        label: Joomla.Text._('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_BTN_CANCEL'),
                        onClick: () => reactivateDialog.destroy(),
                        className: 'btn btn-outline-danger'
                    },
                    {
                        label: Joomla.Text._('COM_QUIZTOOLS_ORDER_DIALOG_REACTIVATE_BTN_REACTIVATE'),
                        onClick: () => orderReactivate(),
                        className: 'btn btn-outline-success ms-2 btn-reactivate'
                    },
                ]
            })
            reactivateDialog.show()
        })

        function orderReactivate() {
            let reactivateForm = document.querySelector('#reactivate-form')

            if (reactivateForm) {
                reactivateForm.submit()
            }
        }
    }
})
