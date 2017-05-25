<nav id="section-nav" class="navigation vertical">
    <?php
    $navArray = array(
        array(
            'label' => __('Tasks'),
            'uri' => url(array(), 'IiifItemsReannotate_Tasks'),
        ),
        array(
            'label' => __('Status'),
            'uri' => url(array(), 'IiifItemsReannotate_Status'),
        ),
    );
    echo nav($navArray);
    ?>
</nav>
