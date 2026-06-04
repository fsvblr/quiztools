import JoomlaDialog from 'joomla.dialog';

(function () {
    const siteRoot = Joomla.getOptions('com_quiztools.question.hotspotsmultiple').siteRoot || '/'
    const containerName = 'hotspot-image-wrap'
    const defaultColor = '#ff0000'

    let subform = null,
        imageField = null,
        fabricCanvas = null,
        currentRow = null

    // temporary drawing state
    let drawingPoints = [],
        drawingActive = false,
        tempPolygon = null,    // preview polygon (semi-transparent + dashed)
        tempCircles = []

    function getCurrentColor() {
        const colorInput = currentRow?.querySelector('input[name$="[color]"]')
        return colorInput ? colorInput.value : defaultColor
    }

    function getOrCreateContainer() {
        let wrap = document.getElementById(containerName)
        if (!wrap) {
            const target = document.querySelector('#fieldset-optionsdata')
            if (!target) {
                return null
            }
            wrap = document.createElement('div')
            wrap.id = containerName
            wrap.classList.add(containerName)
            wrap.style.overflow = 'auto'
            target.appendChild(wrap)
        }
        return wrap
    }

    function getImageUrl() {
        if (!imageField || !imageField.value) {
            return null
        }
        let url = imageField.value.split('#')[0]
        return siteRoot + url
    }

    function initCanvas() {
        const wrap = getOrCreateContainer()
        if (!wrap) {
            return
        }
        wrap.innerHTML = ''
        const url = getImageUrl()
        if (!url) {
            return
        }
        const img = new Image()
        img.crossOrigin = 'anonymous'
        img.onload = () => {
            const canvasEl = document.createElement('canvas')
            canvasEl.id = 'hotspots-canvas'
            wrap.appendChild(canvasEl)
            if (fabricCanvas) {
                fabricCanvas.dispose()
            }
            fabricCanvas = new fabric.Canvas(canvasEl, {
                width: img.naturalWidth,
                height: img.naturalHeight,
            })
            fabricCanvas.backgroundImage = new fabric.Image(img, {
                scaleX: 1,
                scaleY: 1,
                left: 0,
                top: 0,
                originX: 'left',
                originY: 'top',
            })
            fabricCanvas.requestRenderAll()
            drawSavedPolygons()
        }
        img.src = url
    }

    // Saved polygon handling
    function attachEditHandles(polygon, row) {
        detachEditHandles(row)

        const circles = []
        const pnts = polygon.points

        pnts.forEach((pt, idx) => {
            const circle = new fabric.Circle({
                left: pt.x,
                top: pt.y,
                radius: 5,
                fill: polygon.stroke,
                stroke: '#333',
                strokeWidth: 1,
                originX: 'center',
                originY: 'center',
                selectable: true,
                hasControls: false,
                hasBorders: false,
                evented: true,
            })
            circle._polygonOwner = polygon
            circle._vertexIndex = idx
            circle._row = row

            circle.on('moving', function () {
                const oldPoly = this._polygonOwner
                const i = this._vertexIndex
                const rowLocal = this._row
                const newPoints = oldPoly.points.slice()
                newPoints[i] = { x: this.left, y: this.top }
                fabricCanvas.remove(oldPoly)
                const newPoly = new fabric.Polygon(newPoints, {
                    fill: oldPoly.fill,
                    stroke: oldPoly.stroke,
                    strokeWidth: oldPoly.strokeWidth,
                    selectable: false,
                    evented: false,
                })
                fabricCanvas.add(newPoly)
                rowLocal._polygon = newPoly
                rowLocal._editCircles.forEach((c) => {
                    c._polygonOwner = newPoly
                })
                fabricCanvas.requestRenderAll()
                scheduleUpdateCoord(rowLocal, newPoly)
            })

            circles.push(circle)
            fabricCanvas.add(circle)
            // Move circle to top (Fabric 7.1)
            fabricCanvas.bringObjectToFront(circle)
        })

        row._editCircles = circles
        row._polygon = polygon

        polygon.selectable = false
        polygon.evented = false
    }

    function detachEditHandles(row) {
        if (row._editCircles) {
            row._editCircles.forEach((circ) => {
                fabricCanvas.remove(circ)
            })
        }
        row._editCircles = null
    }

    function updateCoordInput(row, poly) {
        const coordsInput = row.querySelector('input[name$="[coordinates]"]')
        if (!coordsInput) {
            return
        }
        const w = fabricCanvas.width
        const h = fabricCanvas.height
        const percentPoints = poly.points.map((p) => {
            return [
                (p.x / w) * 100,
                (p.y / h) * 100,
            ]
        })
        coordsInput.value = JSON.stringify(percentPoints)
        coordsInput.style.borderColor = ''
    }

    function scheduleUpdateCoord(row, poly) {
        if (row._coordTimeout) {
            clearTimeout(row._coordTimeout)
        }
        row._coordTimeout = setTimeout(() => {
            updateCoordInput(row, poly)
        }, 50)
    }

    function drawSavedPolygons() {
        if (!subform || !fabricCanvas) {
            return
        }
        const rows = subform.querySelectorAll('.subform-repeatable-container .subform-repeatable-group')
        rows.forEach((row) => {
            const coordsInput = row.querySelector('input[name$="[coordinates]"]')
            if (!coordsInput || !coordsInput.value) {
                return
            }
            let points
            try {
                points = JSON.parse(coordsInput.value)
            } catch (e) {
                return
            }
            if (!Array.isArray(points) || points.length < 3) {
                return
            }
            const colorInput = row.querySelector('input[name$="[color]"]')
            const color = colorInput ? colorInput.value : defaultColor
            const w = fabricCanvas.width
            const h = fabricCanvas.height
            const pts = points.map((p) => {
                return {
                    x: parseFloat(p[0]) / 100 * w,
                    y: parseFloat(p[1]) / 100 * h,
                }
            })
            const poly = new fabric.Polygon(pts, {
                fill: 'transparent',
                stroke: color,
                strokeWidth: 2,
                selectable: false,
                evented: false,
            })
            fabricCanvas.add(poly)
            attachEditHandles(poly, row)
        })
        fabricCanvas.requestRenderAll()
    }

    // Image change
    function handleImageChange() {
        if (subform) {
            const rows = subform.querySelectorAll('.subform-repeatable-container .subform-repeatable-group')
            rows.forEach((row) => {
                detachEditHandles(row)
                const removeBtn = row.querySelector('.group-remove')
                if (removeBtn) {
                    removeBtn.click()
                }
            })
        }
        if (fabricCanvas) {
            const objs = fabricCanvas.getObjects()
            objs.forEach((o) => {
                fabricCanvas.remove(o)
            })
            fabricCanvas.renderAll()
        }
        initCanvas()
    }

    function handlingSubformRow(row) {
        if (!row) {
            return
        }

        const coordsInput = row.querySelector('input[name$="[coordinates]"]')
        const colorInput = row.querySelector('input[name$="[color]"]')

        if (!coordsInput) {
            return
        }

        const container = coordsInput.parentElement
        const drawBtn = document.createElement('button')
        drawBtn.type = 'button'
        drawBtn.className = 'btn btn-small btn-primary'
        drawBtn.textContent = Joomla.Text._('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_ADMIN_BTN_DRAW')
        drawBtn.style.marginLeft = '10px'
        container.style.display = 'flex'
        container.appendChild(drawBtn)

        const closeBtn = document.createElement('button')
        closeBtn.type = 'button'
        closeBtn.className = 'btn btn-small btn-secondary'
        closeBtn.textContent = Joomla.Text._('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_ADMIN_BTN_STOP')
        closeBtn.style.marginLeft = '5px'
        container.appendChild(closeBtn)

        drawBtn.addEventListener('click', () => {
            if (currentRow) {
                cancelDrawing()
            }
            // Remove existing polygon for this row if present
            if (row._polygon) {
                detachEditHandles(row)
                fabricCanvas.remove(row._polygon)
                row._polygon = null
                fabricCanvas.requestRenderAll()
            }
            // Clear any pending coordinate update timeouts
            if (row._coordTimeout) {
                clearTimeout(row._coordTimeout)
                row._coordTimeout = null
            }
            // Remove existing coordinates for this row if present
            const coordsInput = row.querySelector('input[name$="[coordinates]"]')
            if (coordsInput) {
                coordsInput.value = ''
                coordsInput.style.borderColor = 'red'
            }

            currentRow = row
            enableDrawingMode()
        })

        closeBtn.addEventListener('click', () => {
            finalizeDrawing()
        })

        // Update polygon color in real time when row color changes
        if (colorInput) {
            colorInput.addEventListener('selectionchange', (event) => {
                const newColor = event.target.value
                if (row._polygon) {
                    row._polygon.set('stroke', newColor)
                }
                if (row._editCircles) {
                    row._editCircles.forEach((circle) => {
                        circle.set('fill', newColor)
                    })
                }
                // If currently drawing for this row, update drawing preview
                if (currentRow === row && drawingActive) {
                    updateDrawing()
                }
                if (fabricCanvas) {
                    fabricCanvas.requestRenderAll()
                }
            })
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        subform = document.querySelector('#subfieldList_jform_hotspots')

        imageField = document.querySelector('#jform_image')
        if (imageField) {
            imageField.addEventListener('change', handleImageChange)
        }

        const rows = subform.querySelectorAll('.subform-repeatable-container .subform-repeatable-group')
        rows.forEach((row) => {
            handlingSubformRow(row)
        })

        initCanvas()
    })

    // Subform row add
    document.addEventListener('subform-row-add', (e) => {
        const row = e.detail.row
        handlingSubformRow(row)
    })

    // Drawing functions
    function enableDrawingMode() {
        if (!fabricCanvas) {
            return
        }

        clearTempObjects()

        drawingPoints = []
        drawingActive = true

        fabricCanvas.off('mouse:down')
        fabricCanvas.off('mouse:move')
        fabricCanvas.off('mouse:up')

        fabricCanvas.on('mouse:down', (opt) => {
            if (!drawingActive) {
                return
            }
            const clientX = opt.e.clientX
            const clientY = opt.e.clientY
            const canvasEl = fabricCanvas.lowerCanvasEl || fabricCanvas.getElement()
            const rect = canvasEl.getBoundingClientRect()
            const x = clientX - rect.left
            const y = clientY - rect.top

            // Always add point (no automatic close detection)
            drawingPoints.push({ x: x, y: y })
            updateDrawing()
        })

        const keyHandler = (e) => {
            if (e.key === 'Escape') {
                if (currentRow) {
                    const coordsInput = currentRow.querySelector('input[name$="[coordinates]"]')
                    if (coordsInput) {
                        coordsInput.value = ''
                        coordsInput.style.borderColor = 'red'
                    }
                }
                cancelDrawing()
                document.removeEventListener('keydown', keyHandler)
            }
        }
        document.addEventListener('keydown', keyHandler)
    }

    function updateDrawing() {
        if (!fabricCanvas || drawingPoints.length < 2) {
            return
        }
        clearTempObjects()

        const pts = drawingPoints
        const colorToUse = getCurrentColor()

        // Create a preview polygon (auto‑closes, fill translucent, dashed)
        const polyPts = pts.map((p) => {
            return { x: p.x, y: p.y }
        })

        tempPolygon = new fabric.Polygon(polyPts, {
            fill: 'rgba(255,0,0,0.15)',
            stroke: colorToUse,
            strokeWidth: 2,
            strokeDashArray: [6, 4],
            selectable: false,
            evented: false,
        })
        fabricCanvas.add(tempPolygon)

        // Draw vertex circles
        pts.forEach((p) => {
            const circle = new fabric.Circle({
                left: p.x,
                top: p.y,
                radius: 4,
                fill: colorToUse,
                stroke: '#000',
                strokeWidth: 1,
                originX: 'center',
                originY: 'center',
                selectable: false,
                evented: false,
            })
            tempCircles.push(circle)
            fabricCanvas.add(circle)
        })

        // Update coordinates field in real‑time (percent)
        if (currentRow) {
            const coordsInput = currentRow.querySelector('input[name$="[coordinates]"]')
            if (coordsInput) {
                if (pts.length > 2) {
                    const w = fabricCanvas.width
                    const h = fabricCanvas.height
                    const percentPoints = pts.map((p) => {
                        return [
                            (p.x / w) * 100,
                            (p.y / h) * 100,
                        ]
                    })
                    coordsInput.value = JSON.stringify(percentPoints)
                    coordsInput.style.borderColor = ''
                } else {
                    coordsInput.value = ''
                    coordsInput.style.borderColor = 'red'
                }
            }
        }

        fabricCanvas.requestRenderAll()
    }

    function isPolygonSimple(rawPoints) {
        // Convert the array of arrays [[X, Y], ...] to an array of objects [{x: X, y: Y}, ...]
        let points = rawPoints.map(p => ({ x: p[0], y: p[1] }))

        // If the first and last points coincide, we temporarily remove the last one
        // so as not to consider it as a separate edge.
        if (points.length > 1 &&
            points[0].x === points[points.length - 1].x &&
            points[0].y === points[points.length - 1].y
        ) {
            points = points.slice(0, -1)
        }

        const count = points.length
        if (count < 3) {
            return false
        }

        // Function for checking the intersection of two segments
        function lineSegmentsIntersect(p1, p2, p3, p4) {
            function ccw(a, b, c) {
                return (b.x - a.x) * (c.y - a.y) - (b.y - a.y) * (c.x - a.x)
            }
            const ccw1 = ccw(p1, p3, p4)
            const ccw2 = ccw(p2, p3, p4)
            const ccw3 = ccw(p1, p2, p3)
            const ccw4 = ccw(p1, p2, p4)

            // Segments intersect if the points of one lie on opposite sides of the other (and vice versa)
            return ((ccw1 > 0 && ccw2 < 0) || (ccw1 < 0 && ccw2 > 0)) &&
                ((ccw3 > 0 && ccw4 < 0) || (ccw3 < 0 && ccw4 > 0))
        }

        // Pairwise check of all sides
        for (let i = 0; i < count; i++) {
            for (let j = i + 1; j < count; j++) {
                // Are these adjacent sides?
                const isAdjacent = (i === j) ||
                    (i === 0 && j === count - 1) ||
                    (Math.abs(i - j) === 1)

                if (isAdjacent) {
                    continue
                }

                const p1 = points[i]
                const p2 = points[(i + 1) % count]
                const p3 = points[j]
                const p4 = points[(j + 1) % count]

                if (lineSegmentsIntersect(p1, p2, p3, p4)) {
                    return false // Intersection of sides found
                }
            }
        }

        return true
    }

    function finalizeDrawing() {
        if (!drawingActive || !currentRow) {
            return
        }

        drawingActive = false
        fabricCanvas.off('mouse:down')
        fabricCanvas.off('mouse:move')
        fabricCanvas.off('mouse:up')
        document.removeEventListener('keydown', null)

        const coordsInput = currentRow.querySelector('input[name$="[coordinates]"]')
        const colorInput = currentRow.querySelector('input[name$="[color]"]')

        if (!coordsInput) {
            clearTempObjects()
            return
        }

        if (drawingPoints.length < 3) {
            coordsInput.style.borderColor = 'red'
            clearTempObjects()
            return
        }

        // Auto‑close the polygon (append first point)
        const closedPoints = drawingPoints.slice()
        closedPoints.push({ x: drawingPoints[0].x, y: drawingPoints[0].y })

        const w = fabricCanvas.width
        const h = fabricCanvas.height
        const percentPoints = closedPoints.map((p) => {
            return [
                (p.x / w) * 100,
                (p.y / h) * 100,
            ]
        })

        coordsInput.value = JSON.stringify(percentPoints)

        if (isPolygonSimple(percentPoints)) {
            coordsInput.style.borderColor = ''
        } else {
            coordsInput.style.borderColor = 'red'
            JoomlaDialog.alert(
                Joomla.Text._('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_ALERT_POLYGON_NOT_SIMPLE_BODY'),
                Joomla.Text._('PLG_QUIZTOOLS_HOTSPOTSMULTIPLE_ALERT_POLYGON_NOT_SIMPLE_HEADER')
            )
        }

        // Remove temporary objects
        clearTempObjects()

        const color = colorInput ? colorInput.value : defaultColor
        const poly = new fabric.Polygon(closedPoints, {
            fill: 'transparent',
            stroke: color,
            strokeWidth: 2,
            selectable: false,
            evented: false,
        })

        fabricCanvas.add(poly)
        attachEditHandles(poly, currentRow)
        if (currentRow._coordTimeout) {
            clearTimeout(currentRow._coordTimeout)
            currentRow._coordTimeout = null
        }

        drawingPoints = []
        currentRow = null

        fabricCanvas.requestRenderAll()
    }

    function clearTempObjects() {
        if (tempPolygon) {
            fabricCanvas.remove(tempPolygon)
            tempPolygon = null
        }
        tempCircles.forEach((c) => {
            if (c && fabricCanvas) {
                fabricCanvas.remove(c)
            }
        })
        tempCircles = []
    }

    function cancelDrawing() {
        if (!drawingActive) {
            return
        }
        drawingActive = false
        fabricCanvas.off('mouse:down')
        fabricCanvas.off('mouse:move')
        fabricCanvas.off('mouse:up')
        document.removeEventListener('keydown', null)
        clearTempObjects()
        if (currentRow) {
            if (currentRow._coordTimeout) {
                clearTimeout(currentRow._coordTimeout)
                currentRow._coordTimeout = null
            }
        }
        drawingPoints = []
        if (currentRow) {
            const coordsInput = currentRow.querySelector('input[name$="[coordinates]"]')
            if (coordsInput) {
                coordsInput.style.borderColor = ''
            }
        }
        currentRow = null
    }

    // Remove row handling
    document.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.group-remove')
        if (!removeBtn) {
            return
        }
        const row = removeBtn.closest('.subform-repeatable-group')
        if (!row) {
            return
        }

        detachEditHandles(row)
        if (row._polygon) {
            fabricCanvas.remove(row._polygon)
            row._polygon = null
        }
        fabricCanvas.requestRenderAll()
    })

    // Color change
    document.addEventListener('selectionchange', (e) => {
        const input = document.activeElement
        if (!input || typeof input.matches !== 'function' || !input.matches('input[name$="[color]"]')) {
            return
        }

        const row = input.closest('.subform-repeatable-group')
        if (!row) {
            return
        }

        const newColor = input.value

        if (row._polygon) {
            row._polygon.set('stroke', newColor)
        }

        if (row._editCircles) {
            row._editCircles.forEach((circle) => {
                circle.set('fill', newColor)
            })
        }

        fabricCanvas.requestRenderAll()
    })

})()
