<!DOCTYPE html>
<html>
    <head>
        <style>
            body {
                position: absolute;
                left: 0;
                right: 0;
                top: 0;
                bottom: 0;
            }
            #osdCanvas1 {
                width: 100%;
                height: 100%;
            }
            .iiif-crop-selection {
                position:absolute;
                background-color: rgba(255,255,255,0.1);
                border: 2px solid <?php echo $selectorColour; ?>;
                /*
                The following shadow makes up the
                "mask" that sits over the rest of
                the canvas around the selected area.
                The first parameter prevents the shadow
                from "spreading", making it opaque.
                */
                box-shadow: 0 0 0 10000px rgba(0,0,0,0.5);
                cursor: move;
                transition: opacity 0.2s ease-out;
                opacity:1;
            }
            .iiif-crop-selection.disabled {
                transition: opacity 0.2s ease-out;
                opacity:0;
            }
            .iiif-crop-dragNode {
                border:2px solid <?php echo $selectorColour; ?>;
                border-radius: 20px;
                height: 12px;
                width: 12px;
                box-sizing: border-box;
                background: black;
            }
            .iiif-crop-top-drag-handle {
                position:absolute;
                top:-7px;
                width: 100%;
                height: 14px;
                cursor: ns-resize;
            }
            .iiif-crop-bottom-drag-handle {
                position:absolute;
                bottom:-7px;
                width: 100%;
                height: 14px;
                cursor: ns-resize;
            }
            .iiif-crop-left-drag-handle {
                position:absolute;
                left:-7px;
                width: 14px;
                height: 100%;
                cursor: ew-resize;
            }
            .iiif-crop-right-drag-handle {
                position:absolute;
                right:-7px;
                width: 14px;
                height: 100%;
                cursor: ew-resize;
            }
            .iiif-crop-top-left-drag-node {
                position:absolute;
                top:-6px;
                left:-6px;
                cursor: nw-resize;
            }
            .iiif-crop-top-right-drag-node {
                position:absolute;
                top:-6px;
                right:-6px;
                cursor: ne-resize;
            }
            .iiif-crop-bottom-left-drag-node {
                position:absolute;
                bottom:-6px;
                left:-6px;
                cursor: sw-resize;
            }
            .iiif-crop-bottom-right-drag-node {
                position:absolute;
                bottom:-6px;
                right:-6px;
                cursor: se-resize;
            }
        </style>
    </head>
    <body>
        <div id="osdCanvas1" class="osd-container"></div>
        <script src="<?php echo src('openseadragon', 'js/iiif-cropper', 'js'); ?>"></script>
        <script src="<?php echo src('iiif-osd-crop', 'js/iiif-cropper', 'js'); ?>"></script>
        <script>
        var osdCanvas = new OpenSeadragon({
            id: 'osdCanvas1',
            preserveViewport: true,
            showNavigationControl: false,
            constrainDuringPan: true,
            tileSources: <?php echo json_encode($tileSources); ?>
        });
        var lastRegion = [<?php echo join(',', $xywh); ?>];
        <?php if ($status != 'passed') : ?>
        function updateSize() {
            osdCanvas.cropper.setRegion(lastRegion[0], lastRegion[1], lastRegion[2], lastRegion[3]);
        }
        osdCanvas.addHandler('open', function() {
            osdCanvas.iiifCrop();
            updateSize();
            document.getElementsByClassName('iiif-crop-selection')[0].addEventListener('mouseup', function() {
                lastRegion = osdCanvas.cropper.getIiifSelection().getRegion();
            });
        });
        osdCanvas.addHandler('rotate', function() {
            setTimeout(updateSize, 1);
        });
        osdCanvas.addHandler('resize', function() {
            setTimeout(updateSize, 1);
        });
        osdCanvas.addHandler('pan', function() {
            setTimeout(updateSize, 1);
        });
        osdCanvas.addHandler('zoom', function() {
            setTimeout(updateSize, 1);
        });
        <?php endif; ?>
        </script>
    </body>
</html>
