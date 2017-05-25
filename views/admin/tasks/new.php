<?php
echo head(array(
    'title' => __("Annotation Remapper"),
));
include __DIR__ . '/../../helpers/nav.php';
echo flash();
?>

<h2><?php echo __("New Remap Task"); ?></h2>

<section class="seven columns alpha">
    <?php echo new IiifItemsReannotate_Form_NewTask(); ?>
</section>

<section class="three columns omega">
    <div id="save" class="panel">
        <input type="submit" value="Create Task" class="submit big green button" onclick="jQuery('#new_task_form').submit();">
        <a href="<?php echo admin_url(array(), 'IiifItemsReannotate_Tasks'); ?>" class="big red button">Cancel</a>
    </div>
</section>

<?php echo foot();