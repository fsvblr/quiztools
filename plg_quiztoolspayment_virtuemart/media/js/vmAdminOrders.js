(() => {

    /**
     * Converts a simple object containing query string parameters to a single, escaped query string.
     * This method is a necessary evil since Joomla.request can only accept data as a string.
     *
     * @param    object   {object}  A plain object containing the query parameters to pass
     * @param    prefix   {string}  Prefix for array-type parameters
     *
     * @returns  {string}
     */
    const interpolateParameters = (object, prefix = '') => {
        let encodedString = ''
        Object.keys(object).forEach(prop => {
            if (typeof object[prop] !== 'object') {
                if (encodedString.length > 0) {
                    encodedString += '&'
                }

                if (prefix === '') {
                    encodedString += `${encodeURIComponent(prop)}=${encodeURIComponent(object[prop])}`
                } else {
                    encodedString += `${encodeURIComponent(prefix)}[${encodeURIComponent(prop)}]=${encodeURIComponent(object[prop])}`
                }

                return
            }

            encodedString += `${interpolateParameters(object[prop], prop)}`
        })

        return encodedString
    }

    document.addEventListener('DOMContentLoaded', () => {
        const token = Joomla.getOptions('csrf.token')
        const paths = Joomla.getOptions('system.paths')

        const action = Joomla.getOptions('quiztoolspayment.virtuemart.action', '')
        const cid = Joomla.getOptions('quiztoolspayment.virtuemart.cid', '[]')

        // Removal an order in VM => removal related orders in QuizTools
        if (action === 'remove' && cid !== '[]') {
            const postData = {
                option: 'com_ajax',
                group: 'quiztoolspayment',
                plugin: 'virtuemart',
                format: 'raw',
                action: action,
                cid: cid
            }
            postData[token] = 1

            Joomla.request({
                url: `${paths ? `${paths.base}/index.php` : window.location.pathname}?${Joomla.getOptions('csrf.token')}=1`,
                method: 'POST',
                data: interpolateParameters(postData),
                onSuccess(rawResponse) {
                    // nothing
                },
                onError: xhr => {
                    // nothing
                }
            })
        }
    })

})()
