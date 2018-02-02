<?php
echo head(array(
    'title' => __("Annotation Remapper"),
));
include __DIR__ . '/../../helpers/nav.php';
?>
<div id="primary">
<h2><?php echo __('Status Panel'); ?></h2>  
<?php if (empty($statuses)): ?>
    <p>No tasks have been run yet.</p>
<?php else: ?>
<?php echo pagination_links(); ?>

<table>
    <thead>
    <tr>
        <?php
        $browseHeadings[__('Task')] = 'source';
        $browseHeadings[__('Done')] = null;
        $browseHeadings[__('Skipped')] = null;
        $browseHeadings[__('Failed')] = null;
        $browseHeadings[__('Date')] = 'added';
        $browseHeadings[__('Status')] = 'status';
        echo browse_sort_links($browseHeadings, array('link_tag' => 'th scope="col"', 'list_tag' => '')); ?>
    </tr>
    </thead>
    <tbody>
        
<?php $statusIds = array(); ?>
<?php foreach ($statuses as $status): ?>
    <?php $statusIds[] = $status->id; ?>
    <tr id="status-<?php echo $status->id; ?>">
        <td><?php echo html_escape($status->source); ?></td>
        <td><?php echo $status->dones; ?></td>
        <td><?php echo $status->skips; ?></td>
        <td><?php echo $status->fails; ?></td>
        <td><?php echo format_date($status->added); ?></td>
        <td><?php echo $status->status; ?></td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php echo pagination_links(); ?>
<?php endif; ?>

<script>
jQuery(function() {
    var ids = <?php echo json_encode($statusIds); ?>;
    if (ids) {
        setInterval(function() {
            jQuery.get(
                <?php echo js_escape(admin_url(array(), 'IiifItemsReannotate_AjaxStatus')); ?>,
                { id: ids }
            ).done(function(data) {
                jQuery.each(data, function(_, entry) {
                    var jqrow = jQuery('#status-' + entry.id);
                    jqrow.find('td:nth-child(2)').html(entry.dones);
                    jqrow.find('td:nth-child(3)').html(entry.skips);
                    jqrow.find('td:nth-child(4)').html(entry.fails);
                    jqrow.find('td:nth-child(6)').html(entry.status);
                });
            });
        }, 2000);
    }
});
</script>
    
</div>
<?php echo foot();