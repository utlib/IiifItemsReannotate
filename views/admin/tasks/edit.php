<?php
echo head(array(
    'title' => __("Annotation Remapper"),
));
include __DIR__ . '/../../helpers/nav.php';
echo flash();
?>
<link rel="stylesheet" href="<?php echo src('main', 'css', 'css'); ?>">

<h2><?php echo __("Remap Annotations"); ?>: <?php echo html_escape($task->name); ?></h2>

<section class="eight columns alpha">
    <div class="row">
        <div class="two columns alpha">Images Mapped: <span id="progress-counter">0 / 0</span></div>
        <progress max="0" value="0" class="six columns omega" id="progress-bar"></progress>
    </div>
    <div class="row">
        <div class="four columns alpha comparison-block">
            <h4><?php echo metadata($sourceCollection, array('Dublin Core', 'Title')); ?></h4>
            <span id="source-progress" class="comparison-progress">-/-</span>
            <iframe id="original-manifest"></iframe>
            <ul class="mapping-actions">
                <li><button id="source-prev" class="blue button">&lt;</button></li>
                <li><button id="mapping-pass" class="blue button"><?php echo __("Pass"); ?></button></li>
                <li><button id="mapping-confirm" class="green button"><?php echo __("Confirm"); ?></button></li>
                <li><button id="mapping-reset" class="red button"><?php echo __("Reset"); ?></button></li>
                <li><button id="source-next" class="blue button">&gt;</button></li>
            </ul>
        </div>
        <div class="four columns omega comparison-block">
            <h4><?php echo metadata($targetCollection, array('Dublin Core', 'Title')); ?></h4>
            <span id="destination-progress" class="comparison-progress">-/-</span>
            <iframe id="destination-manifest"></iframe>
            <ul class="mapping-actions">
                <li><button id="destination-prev" class="blue button">&lt;</button></li>
                <li><button id="destination-next" class="blue button">&gt;</button></li>
            </ul>
        </div>
    </div>
</section>

<section class="two columns omega">
    <div id="save" class="panel">
        <form method="POST">
            <input id="run-job" action="<?php echo admin_url(array('id' => $task->id), 'IiifItemsReannotate_Tasks_Edit'); ?>" type="submit" value="Run Job" class="submit big green button" disabled="disabled">
        </form>
        <a href="<?php echo admin_url(array(), 'IiifItemsReannotate_Tasks'); ?>" class="big blue button"><?php echo __("Return to Tasks"); ?></a>
        <form method="POST" action="<?php echo admin_url(array('id' => $task->id), 'IiifItemsReannotate_Tasks_Delete') ?>">
            <input type="submit" class="big red button" value="<?php echo __("Abandon Job"); ?>">
        </form>
    </div>
</section>

<script>
    jQuery(document).ready(function() {
        var source_num = 0,
            destination_num = 0,
            source_ids = <?php echo '[' . join(',', $sourceItemIds) . ']'; ?>,
            destination_ids = <?php echo '[' . join(',', $destinationItemIds) . ']'; ?>,
            progress = <?php echo $task->countMappings(); ?>,
            progress_max = <?php echo $task->countMaxMappings(); ?>,
            map_status = 'normal',
            last_source_region = null,
            last_destination_region = null,
            recordLastRegions = function () {
                var sr, dr;
                try {
                    sr = document.getElementById('original-manifest').contentWindow.osdCanvas.cropper.getIiifSelection().getRegion(),
                    dr = document.getElementById('destination-manifest').contentWindow.osdCanvas.cropper.getIiifSelection().getRegion();
                    last_source_region = sr;
                    last_destination_region = dr;
                } catch (e) {
                }
            },
            toggleNavigators = function () {
                jQuery("#source-prev").attr('disabled', source_num <= 0);
                jQuery("#source-next").attr('disabled', source_num >= source_ids.length-1);
                jQuery('#destination-prev').attr('disabled', destination_num <= 0 || map_status !== 'normal');
                jQuery('#destination-next').attr('disabled', destination_num >= destination_ids.length-1 || map_status !== 'normal');
            },
            updateProgress = function () {
                jQuery('#progress-counter').html(progress + " / " + progress_max);
                jQuery('#progress-bar').attr({
                    value: progress,
                    max: progress_max
                });
                jQuery('#run-job').attr('disabled', progress === progress_max ? null : 'disabled');
            },
            updatePageNumbers = function () {
                jQuery('#source-progress').html((source_num + 1) + "/" + source_ids.length);
                jQuery('#destination-progress').html((map_status === 'passed') ? '-/-' : ((destination_num + 1) + "/" + destination_ids.length));
            },
            updateToShowInfo = function (data, defaultSourceItemId, defaultTargetItemId) {
                var source = data.hasOwnProperty('nextMapSource') ? data.nextMapSource : data.mapSource,
                    target = data.hasOwnProperty('nextMapTarget') ? data.nextMapTarget : data.mapTarget;
                if (source) {
                    if (target) {
                        map_status = 'confirmed';
                        jQuery('#original-manifest').attr('src', cropperUrl(source.id, { status: 'confirmed', xywh: source.xywh.join(',') }));
                        jQuery('#destination-manifest').html('').attr('src', cropperUrl(target.id, { status: 'confirmed', xywh: target.xywh.join(',') }));
                        jQuery('#mapping-pass').attr('disabled', null);
                        jQuery('#mapping-confirm').attr('disabled', null);
                        jQuery('#mapping-reset').attr('disabled', null);
                    } else {
                        map_status = 'passed';
                        jQuery('#original-manifest').attr('src', cropperUrl(source.id, { status: 'passed' }));
                        jQuery('#destination-manifest').html('<h1>PASSED</h1>').attr('src', null);
                        jQuery('#mapping-pass').attr('disabled', true);
                        jQuery('#mapping-confirm').attr('disabled', true);
                        jQuery('#mapping-reset').attr('disabled', null);
                    }
                    
                } else {
                    map_status = 'normal';
                    if (defaultSourceItemId !== null && defaultSourceItemId !== undefined) {
                        if (last_source_region) {
                            jQuery('#original-manifest').attr('src', cropperUrl(defaultSourceItemId, { xywh: last_source_region.join(',') }));
                        } else {
                            jQuery('#original-manifest').attr('src', cropperUrl(defaultSourceItemId));
                        }
                    }
                    if (defaultTargetItemId !== null && defaultTargetItemId !== undefined) {
                        if (last_destination_region) {
                            jQuery('#destination-manifest').attr('src', cropperUrl(defaultTargetItemId, { xywh: last_destination_region.join(',') }));
                        } else {
                            jQuery('#destination-manifest').attr('src', cropperUrl(defaultTargetItemId));
                        }
                    }
                    jQuery('#mapping-pass').attr('disabled', null);
                    jQuery('#mapping-confirm').attr('disabled', null);
                    jQuery('#mapping-reset').attr('disabled', true);
                }
            },
            cropperUrl = function(itemId, opts) {
                return '../../items/' + itemId + '/cropper' + (opts ? ('?' + jQuery.param(opts)) : '');
            };
            
        jQuery.ajax({
            url: 'info',
            data: {
                src: source_ids[source_num]
            }
        }).done(function (data) {
            source_num = data.mapSource ? source_ids.indexOf(data.mapSource.id) : source_num;
            destination_num = data.mapTarget ? destination_ids.indexOf(data.mapTarget.id) : destination_num;
            updateToShowInfo(data, source_ids[source_num], destination_ids[destination_num]);
            toggleNavigators();
            updateProgress();
            updatePageNumbers();
        }).fail(function() {
            alert('Cannot initialize reannotation session. Please refresh.');
        });
        
        jQuery('#original-manifest, #destination-manifest').on('load', function() {
            toggleNavigators();
            updatePageNumbers();
        });
        
        jQuery('#mapping-confirm').click(function() {
            // Capture IIIF areas
            recordLastRegions();
            // Submit
            jQuery.ajax({
                url: 'confirm',
                method: 'POST',
                data: {
                    src: source_ids[source_num],
                    tgt: destination_ids[destination_num],
                    sx: last_source_region[0],
                    sy: last_source_region[1],
                    sw: last_source_region[2],
                    sh: last_source_region[3],
                    tx: last_destination_region[0],
                    ty: last_destination_region[1],
                    tw: last_destination_region[2],
                    th: last_destination_region[3],
                    next: source_ids[Math.min(source_num + 1, source_ids.length - 1)]
                }
            }).done(function(data) {
                source_num = data.nextMapSource ? source_ids.indexOf(data.nextMapSource.id) : Math.min(source_num + 1, source_ids.length - 1);
                destination_num = data.nextMapTarget ? destination_ids.indexOf(data.nextMapTarget.id) : Math.min(destination_num + 1, destination_ids.length - 1);
                updateToShowInfo(data, source_ids[source_num], destination_ids[destination_num]);
                progress = data.maps;
                progress_max = data.maxMaps;
                updateProgress();
            }).fail(function() {
                alert('Confirmation failed.');
            });
        });
        jQuery('#mapping-pass').click(function() {
            jQuery.ajax({
                url: <?php echo js_escape(admin_url(array('id' => $task->id), 'IiifItemsReannotate_Mappings_Pass')); ?>,
                method: 'POST',
                data: {
                    src: source_ids[source_num],
                    next: source_ids[Math.min(source_num + 1, source_ids.length - 1)]
                }
            }).done(function(data) {
                source_num = data.nextMapSource ? source_ids.indexOf(data.nextMapSource.id) : Math.min(source_num + 1, source_ids.length - 1);
                destination_num = data.nextMapTarget ? destination_ids.indexOf(data.nextMapTarget.id) : destination_num;
                updateToShowInfo(data, source_ids[source_num], null);
                progress = data.maps;
                progress_max = data.maxMaps;
                updateProgress();
            }).fail(function() {
                alert('Pass failed.');
            });
        });
        jQuery('#mapping-reset').click(function() {
            jQuery.ajax({
                url: <?php echo js_escape(admin_url(array('id' => $task->id), 'IiifItemsReannotate_Mappings_Reset')); ?>,
                method: 'POST',
                data: {
                    src: source_ids[source_num]
                }
            }).done(function(data) {
                updateToShowInfo(data, source_ids[source_num], destination_ids[destination_num]);
                progress = data.maps;
                progress_max = data.maxMaps;
                updateProgress();
            }).fail(function() {
                alert('Reset failed.');
            });
        });
        
        jQuery('#source-prev').click(function() {
            if (source_num > 0) {
                recordLastRegions();
                jQuery.ajax({
                    url: 'info',
                    data: {
                        src: source_ids[source_num - 1]
                    }
                }).done(function(data) {
                    if (source_num > 0) {
                        source_num--;
                        destination_num = data.mapTarget ? destination_ids.indexOf(data.mapTarget.id) : (Math.max(0, destination_num - 1));
                        updateToShowInfo(data, source_ids[source_num], destination_ids[destination_num]);
                    }
                }).fail(function() {
                    alert('Cannot contact server.');
                });
            }
        });
        jQuery('#source-next').click(function() {
            if (source_num < source_ids.length - 1) {
                recordLastRegions();
                jQuery.ajax({
                    url: 'info',
                    data: {
                        src: source_ids[source_num + 1]
                    }
                }).done(function(data) {
                    if (source_num < source_ids.length - 1) {
                        source_num++;
                        destination_num = data.mapTarget ? destination_ids.indexOf(data.mapTarget.id) : (Math.min(source_ids.length - 1, destination_num + 1));
                        updateToShowInfo(data, source_ids[source_num], destination_ids[destination_num]);
                    }
                }).fail(function() {
                    alert('Cannot contact server.');
                });
            }
        });
        jQuery('#destination-prev').click(function() {
            if (destination_num > 0) {
                // Capture IIIF areas
                recordLastRegions();
                // Renavigate to next canvas with same size
                jQuery('#destination-manifest').attr('src', cropperUrl(destination_ids[--destination_num], { xywh: last_destination_region.join(',') }));
            }
        });
        jQuery('#destination-next').click(function() {
            if (destination_num < destination_ids.length-1) {
                // Capture IIIF areas
                recordLastRegions();
                // Renavigate to next canvas with same size
                jQuery('#destination-manifest').attr('src', cropperUrl(destination_ids[++destination_num], { xywh: last_destination_region.join(',') }));
            }
        });
    });
</script>

<?php echo foot();