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
        
<?php foreach ($statuses as $status): ?>
    <tr>
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

</div>
<?php echo foot();