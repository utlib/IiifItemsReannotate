<?php
echo head(array(
    'title' => __("Annotation Remapper"),
));
include __DIR__ . '/../../helpers/nav.php';
echo flash();
?>
<div id="primary">
<?php if (empty($tasks)): ?>
    <h2><?php echo __('There are annotation remapping tasks yet.'); ?></h2>
    <?php if (true || is_allowed('IiifItemsReannotate_Tasks','add')): ?>
        <a href="<?php echo admin_url(array(), 'IiifItemsReannotate_Tasks_New') ?>" class="big green add button"><?php echo __("Start New Task") ?></a>
    <?php endif; ?>
    
<?php else: ?>
<h2><?php echo __("Remap Tasks"); ?></h2>
<div class="table-actions">
    <a href="<?php echo admin_url(array(), 'IiifItemsReannotate_Tasks_New') ?>" class="small green add button"><?php echo __("Start New Task") ?></a>
</div>

<?php echo pagination_links(); ?>

<table>
    <thead>
    <tr>
        <?php
        $browseHeadings[__('Title')] = 'name';
        $browseHeadings[__('Mappings')] = null;
        $browseHeadings[__('Source')] = null;
        $browseHeadings[__('Target')] = null;
        $browseHeadings[__('Date')] = 'created';
        echo browse_sort_links($browseHeadings, array('link_tag' => 'th scope="col"', 'list_tag' => '')); ?>
    </tr>
    </thead>
    <tbody>
        
<?php foreach($tasks as $key=>$task): ?>
    <tr>
        <td>
            <a href="<?php echo admin_url(array('id' => $task->id), 'IiifItemsReannotate_Tasks_Edit') ?>"><?php echo html_escape($task->name); ?></a>
        </td>
        <td><?php echo $task->countMappings(); ?>/<?php echo $task->countMaxMappings(); ?></td>
        <td><?php $source = $task->getSourceCollection(); ?><a href="<?php echo admin_url(array('id' => $source->id, 'controller' => 'collections', 'action' => 'show'), 'id'); ?>"><?php echo metadata($source, array('Dublin Core', 'Title')); ?></a></td>
        <td><?php $target = $task->getTargetCollection(); ?><a href="<?php echo admin_url(array('id' => $target->id, 'controller' => 'collections', 'action' => 'show'), 'id'); ?>"><?php echo metadata($target, array('Dublin Core', 'Title')); ?></a></td>
        <td><?php echo format_date($task->created); ?></td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>
<?php echo pagination_links(); ?>
<?php endif; ?>

</div>
<?php echo foot();