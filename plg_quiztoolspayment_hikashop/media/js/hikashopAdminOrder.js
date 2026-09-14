(() => {
    document.addEventListener('DOMContentLoaded', () => {
        const btnSelectUser = document.querySelector('#hikashop_order_field_user a')
        if (btnSelectUser) {
            btnSelectUser.removeAttribute('onclick')
            btnSelectUser.addEventListener('click', (event) => {
                event.preventDefault()
                Joomla.renderMessages({ warning: [Joomla.Text._('PLG_QUIZTOOLSPAYMENT_HIKASHOP_CHANGING_USER_IN_ORDER_WARNING')] })
            })
        }
    })
})()
