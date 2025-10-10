(function () {

    let canvas = null;

    // Upload image
    document.querySelector('#jform_upload_image').addEventListener('change', function() {
        document.querySelector('#certificate-form-task').value = 'certificate.uploadImage';
        document.querySelector('#certificate-form').submit();
    });

    // Select Image
    document.querySelector('#jform_file').addEventListener('change', function(event) {
        canvasRender(event.target.value, false);
    });

    document.addEventListener('DOMContentLoaded', function() {
        let selectedImage = document.querySelector('#jform_file').value;
        if (selectedImage) {
            let subformFields = document.querySelector('.subform-fields > .control-group');
            subformFields.style.display = 'block';

            canvasRender(selectedImage, true);
        }
    });

    function canvasRender(imageName, initialPageLoad) {
        if (!initialPageLoad) {
            let removeBtns = document.querySelectorAll('.subform-fields .group-remove');
            if (removeBtns.length > 0) {
                removeBtns.forEach(btn => {
                    btn.click();
                });
            }
        }

        const wrap = document.querySelector('#certificate-canvas-wrap');
        wrap.innerHTML = '';

        // Workaround: The 'showon' field attribute fires faster than the canvas background image loads.
        // And it is possible to click the 'add field' button in the subform ahead of time.
        let subformFields = document.querySelector('.subform-fields > .control-group');
        subformFields.style.display = 'none';

        if (canvas) {
            canvas = null;
        }

        if (imageName === '') {
            return false;
        }

        const wrapStyles = getComputedStyle(wrap);
        const wrapWidth = parseFloat(wrapStyles.width) * 0.96;

        const c = document.createElement('canvas');
        c.id = 'certificate-canvas';
        c.width = wrapWidth;
        c.height = '600';
        wrap.appendChild(c);

        let image = new Image();
        image.src = '/images/quiztools/certificates/' + imageName;

        image.onload = () => {
            subformFields.style.display = 'block';
            let maxWidth = image.width < wrapWidth ? image.width : wrapWidth;

            canvas = new fabric.Canvas(c, {
                originX: 'left',
                originY: 'top',
                width: maxWidth,
                height: (image.height / image.width) * maxWidth,
                backgroundImage: new fabric.Image(image),
            });
            canvas.backgroundImage.scaleToWidth(maxWidth);
            canvas.backgroundImage.scaleToHeight((image.height / image.width) * maxWidth);
            canvas.renderAll();

            // Changes in text and coordinates (drag'n'drop) -> to subform fields
            canvas.on('mouse:up', (event) => {
                if (event.target && event.target.hasOwnProperty('text') && event.target.hasOwnProperty('id')) {
                    populateSubformFields(event.target);
                }
            });

            // Changes in text and coordinates (drag'n'drop) -> to subform fields
            canvas.on('text:changed', (event) => {
                if (event.target && event.target.hasOwnProperty('text') && event.target.hasOwnProperty('id')) {
                    populateSubformFields(event.target);
                }
            });

            if (initialPageLoad) {
                addTextToCanvasOnInitialPageLoad();
            }
        };
    }

    // Adding a subform row.
    document.addEventListener('subform-row-add', ({ detail: { row } }) => {
        canvasPopulate(row);
    });

    // Adding text to canvas.
    function canvasPopulate(row) {
        let n = row.dataset.group.replace(/[^0-9]/g, ''),
            textString = row.querySelector('#jform_fields__fields' + n + '__text'),
            coordX = row.querySelector('#jform_fields__fields' + n + '__x'),
            coordY = row.querySelector('#jform_fields__fields' + n + '__y'),
            fontFamily = row.querySelector('#jform_fields__fields' + n + '__font_family'),
            fontSize = row.querySelector('#jform_fields__fields' + n + '__font_size'),
            colorpicker = row.querySelector('#jform_fields__fields' + n + '__color');

        let fontSizeInPixels = Math.round(fontSize.value * 1.328147);  // pt => px

        let text = new fabric.IText(textString.value, {
            left: parseInt(coordX.value),
            top: parseInt(coordY.value),
            originY: 'center',
            fontFamily: fontFamily.value,
            fontSize: fontSizeInPixels,  // in pixels
            fill: colorpicker.value,
            lockRotation: true,
            lockScalingX: true,
            lockScalingY: true,
            cornerSize: 1,
            id: 'canvasText_' + n,
        });
        text.controls.mtr.visible = false;
        canvas.add(text);

        // Сhanges in font -> to canvas
        fontFamily.addEventListener('input', function(event) {
            text.set('fontFamily', event.target.value);
            canvas.renderAll();
        });

        // Сhanges in font size -> to canvas
        fontSize.addEventListener('input', function(event) {
            let fontSizeInPixels = Math.round(event.target.value * 1.328147);  // pt => px
            text.set('fontSize', fontSizeInPixels);
            canvas.renderAll();
        });

        // Сhanges in color -> to canvas
        colorpicker.addEventListener('selectionchange', function(event) {
            text.set('fill', event.target.value);
            canvas.renderAll();
        });
    }

    // Deleting a subform row. Removing text from the canvas.
    document.addEventListener('subform-row-remove', ({ detail: { row } }) => {
        let n = row.dataset.group.replace(/[^0-9]/g, '');
        canvas.forEachObject(obj => {
            if (obj.id && obj.id === 'canvasText_' + n) {
                canvas.setActiveObject(obj);
                let active = canvas.getActiveObject();
                canvas.remove(active);
            }
        });
    });

    // Changes in text and coordinates (drag'n'drop) -> to subform fields
    function populateSubformFields(el) {
        let id = el.id.replace(/[^0-9]/g, '');
        let row = document.querySelector('.subform-fields tr.subform-repeatable-group[data-group="fields' + id + '"]');

        if (row) {
            row.querySelector('#jform_fields__fields' + id + '__text').value = el.text;
            row.querySelector('#jform_fields__fields' + id + '__x').value = el.left;
            row.querySelector('#jform_fields__fields' + id + '__y').value = el.top;
        }
    }

    // Add text to canvas on initial page load
    function addTextToCanvasOnInitialPageLoad() {
        let rows = document.querySelectorAll('.subform-fields .subform-repeatable-group');
        if (rows.length > 0) {
            rows.forEach(row => {
                canvasPopulate(row);
            });
        }
    }

})();
