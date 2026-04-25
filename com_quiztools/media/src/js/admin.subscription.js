(function () {
    document.addEventListener('DOMContentLoaded', () => {

        const form = document.querySelector('#subscription-form')
        const paymentMethod = form.querySelector('#jform_payment_method')

        const allSelectsProduct = form.querySelectorAll(
            'select[id^="jform_select_product_id__"], input[id^="jform_select_product_id__"]'
        )

        let formHasDisabled = true

        // Dynamically adding the "required" attribute to product selection when changing payment options.
        function selectProductAttributes() {
            allSelectsProduct.forEach(el => {
                el.removeAttribute('required')
            })

            if (paymentMethod.value === 'manual') {
                return false
            }

            let currentSelectProduct = form.querySelector('#jform_select_product_id__' + paymentMethod.value)

            if (currentSelectProduct) {
                currentSelectProduct.setAttribute('required', 'required')
            }
        }

        if (paymentMethod) {
            selectProductAttributes()

            paymentMethod.addEventListener('change', (e) => {
                selectProductAttributes()
            })

            // Before submitting the form, remove 'disabled' attribute
            form.addEventListener('submit', (e) => {
                if (formHasDisabled) {
                    e.preventDefault()

                    paymentMethod.removeAttribute('disabled')

                    allSelectsProduct.forEach(el => {
                        el.removeAttribute('disabled')
                    })

                    formHasDisabled = false
                    form.submit()
                }
            })
        }

    })
})()
