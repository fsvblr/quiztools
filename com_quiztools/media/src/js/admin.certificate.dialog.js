import JoomlaDialog from 'joomla.dialog';

window.addEventListener('load', function() {
    let btnPreviewConfirm = document.querySelector('.button-preview-confirm'),
        certificateId = Joomla.getOptions('com_quiztools.certificate').id,
        certificateTitle = Joomla.getOptions('com_quiztools.certificate').title,
        token = Joomla.getOptions('csrf.token', ''),
        confirmDialog,
        certificateDialog

    if(btnPreviewConfirm) {
        btnPreviewConfirm.addEventListener('click', function() {
            confirmDialog = JoomlaDialog.confirm(
                Joomla.Text._('COM_QUIZTOOLS_CERTIFICATE_CONFIRM_PREVIEW_BODY'),
                Joomla.Text._('COM_QUIZTOOLS_CERTIFICATE_CONFIRM_PREVIEW_TITLE')
            )
            .then((result) => {
                if(result) {
                    certificateDialog = new JoomlaDialog({
                        popupType: 'iframe',
                        textHeader: certificateTitle,
                        src: 'index.php?option=com_quiztools&task=certificate.previewCertificate&id=' + certificateId + '&' + token + '=1',
                    })
                    certificateDialog.show()
                }
            })
        })
    }
})
