(function () {

    const siteRoot = Joomla.getOptions('com_quiztools.certificate').siteRoot || '/'
    const isRtl = document.documentElement.dir === 'rtl'

    // Cache for already loaded fonts, to avoid reloading them
    const loadedFonts = new Set()
    // Path to the fonts folder
    const fontsBaseUrl = siteRoot + 'media/com_quiztools/fonts/'

    let canvas = null

    // Upload image
    if (document.querySelector('#jform_upload_image')) {
        document.querySelector('#jform_upload_image').addEventListener('change', () => {
            document.querySelector('#certificate-form-task').value = 'certificate.uploadImage'
            document.querySelector('body').classList.add('certificate-image-loaded')
            document.querySelector('#certificate-form').submit()
        })
    }

    // Select Image
    document.querySelector('#jform_file').addEventListener('change', (event) => {
        canvasRender(event.target.value, false)
    })

    document.addEventListener('DOMContentLoaded', () => {
        let selectedImage = document.querySelector('#jform_file').value
        if (selectedImage) {
            let subformFields = document.querySelector('.subform-fields > .control-group')
            subformFields.style.display = 'block'

            canvasRender(selectedImage, true)
        }
    })

    function canvasRender(imageName, initialPageLoad) {
        if (!initialPageLoad) {
            let removeBtns = document.querySelectorAll('.subform-fields .group-remove')
            if (removeBtns.length > 0) {
                removeBtns.forEach((btn) => {
                    btn.click()
                })
            }
        }

        const wrap = document.querySelector('#certificate-canvas-wrap')
        wrap.innerHTML = ''

        // Workaround: The 'showon' field attribute fires faster than the canvas background image loads.
        // And it is possible to click the 'add field' button in the subform ahead of time.
        let subformFields = document.querySelector('.subform-fields > .control-group')
        subformFields.style.display = 'none'

        if (canvas) {
            canvas.dispose()
            canvas = null
        }

        if (imageName === '') {
            return false
        }

        const c = document.createElement('canvas')
        c.id = 'certificate-canvas'
        wrap.appendChild(c)

        const image = new Image()

        image.onload = () => {
            subformFields.style.display = 'block'

            canvas = new fabric.Canvas(c, {
                originX: 'left',
                originY: 'top',
                width: image.width,
                height: image.height,
            })

            canvas.backgroundImage = new fabric.Image(image, {
                left: 0,
                top: 0,
                originX: 'left',
                originY: 'top',
                scaleX: 1,
                scaleY: 1
            })
            canvas.requestRenderAll()
            wrap.style.height = parseInt(canvas.height) + 24 + 'px'  // Remove vertical scroll

            // Changes in text and coordinates (drag'n'drop) -> to subform fields
            canvas.on('mouse:up', (event) => {
                if (event.target && event.target.text && event.target.id) {
                    populateSubformFields(event.target)
                }
            })

            // Changes in text and coordinates (drag'n'drop) -> to subform fields
            canvas.on('text:changed', (event) => {
                if (event.target && event.target.text && event.target.id) {
                    populateSubformFields(event.target)
                }
            })

            if (initialPageLoad) {
                addTextToCanvasOnInitialPageLoad()
            }
        }

        image.src = siteRoot + 'images/quiztools/certificates/' + imageName
    }

    // Adding a subform row.
    document.addEventListener('subform-row-add', ({ detail: { row } }) => {
        if (!canvas) {
            return
        }
        canvasPopulate(row)
    })

    // Adding text to canvas.
    function canvasPopulate(row) {
        let n = row.dataset.group.replace(/[^0-9]/g, ''),
            textString = row.querySelector('#jform_fields__fields' + n + '__text'),
            coordX = row.querySelector('#jform_fields__fields' + n + '__x'),
            coordY = row.querySelector('#jform_fields__fields' + n + '__y'),
            fontFamily = row.querySelector('#jform_fields__fields' + n + '__font_family'),
            fontSize = row.querySelector('#jform_fields__fields' + n + '__font_size'),
            colorpicker = row.querySelector('#jform_fields__fields' + n + '__color')

        let fontSizeInPixels = Math.round(fontSize.value * 1.328147)  // pt => px

        let text = new fabric.IText(textString.value, {
            left: parseInt(coordX.value),
            top: parseInt(coordY.value),
            originX: 'left',
            originY: 'top',
            fontSize: fontSizeInPixels,  // in pixels
            fill: colorpicker.value,
            lockRotation: true,
            lockScalingX: true,
            lockScalingY: true,
            cornerSize: 1,
            id: 'canvasText_' + n,
            direction: isRtl ? 'rtl' : 'ltr',
            textAlign: isRtl ? 'right' : 'left',
        })

        text.controls.mtr.visible = false
        canvas.add(text)
        void applyFontToCanvasText(text, fontFamily.value)

        // Changes in font -> to canvas
        fontFamily.addEventListener('input', (event) => {
            void applyFontToCanvasText(text, event.target.value)
        })

        // Сhanges in font size -> to canvas
        fontSize.addEventListener('input', (event) => {
            let fontSizeInPixels = Math.round(event.target.value * 1.328147)  // pt => px
            text.set('fontSize', fontSizeInPixels)
            canvas.requestRenderAll()
        })

        // Сhanges in color -> to canvas
        colorpicker.addEventListener('selectionchange', (event) => {
            text.set('fill', event.target.value)
            canvas.requestRenderAll()
        })
    }

    // Deleting a subform row. Removing text from the canvas.
    document.addEventListener('subform-row-remove', ({ detail: { row } }) => {
        if (!canvas) {
            return
        }
        let n = row.dataset.group.replace(/[^0-9]/g, '')
        canvas.forEachObject(obj => {
            if (obj.id && obj.id === 'canvasText_' + n) {
                canvas.setActiveObject(obj)
                let active = canvas.getActiveObject()
                canvas.remove(active)
            }
        })
    })

    // Changes in text and coordinates (drag'n'drop) -> to subform fields
    function populateSubformFields(el) {
        let id = el.id.replace(/[^0-9]/g, '')
        let row = document.querySelector('.subform-fields tr.subform-repeatable-group[data-group="fields' + id + '"]')

        if (row) {
            row.querySelector('#jform_fields__fields' + id + '__text').value = el.text
            row.querySelector('#jform_fields__fields' + id + '__x').value = el.left
            row.querySelector('#jform_fields__fields' + id + '__y').value = el.top
        }
    }

    // Add text to canvas on initial page load
    function addTextToCanvasOnInitialPageLoad() {
        let rows = document.querySelectorAll('.subform-fields .subform-repeatable-group')
        if (rows.length > 0) {
            rows.forEach((row) => {
                canvasPopulate(row)
            })
        }
    }

    // Loading fonts—not present on the computer—into the browser
    // to ensure better visual rendering of the text on the certificate.
    async function applyFontToCanvasText(textObj, fontValue) {
        if (!fontValue) return

        // Remove the .ttf extension to get a clean name for fontFamily
        const fontName = fontValue.replace(/\.ttf$/i, '')

        const fontFile = fontName + '.ttf'

        if (!loadedFonts.has(fontName)) {
            try {
                // Creating and loading a font from a file
                const fontFace = new FontFace(fontName, `url("${fontsBaseUrl}${fontFile}")`)
                const loadedFace = await fontFace.load()

                // Registering a font in a browser document
                document.fonts.add(loadedFace)
                loadedFonts.add(fontName)
            } catch (err) {
                console.error('Font loading error:', fontValue, err)
                return
            }
        }

        // Apply to the Fabric.js object and redraw the canvas
        textObj.set('fontFamily', fontName)
        textObj.initDimensions() // Recalculates the width and height of letters for a new font
        textObj.setCoords()      // Updates the selection frame

        if (canvas) {
            canvas.requestRenderAll()
        }
    }

})()
