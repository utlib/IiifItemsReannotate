<?php

/**
 * Form for creating a new task.
 * @package IiifItemsReannotate/Form
 */
class IiifItemsReannotate_Form_NewTask extends Omeka_Form {
    /**
     * Build form elements for this form.
     */
    public function init() {
        // Top-level parent
        parent::init();
        $this->applyOmekaStyles();
        $this->setAttrib('id', 'new_task_form');
        $this->setAttrib('method', 'POST');
        $this->setAttrib('action', admin_url(array(), 'IiifItemsReannotate_Tasks_New'));
        // Name
        $this->addElement('text', 'task_name', array(
            'label' => __("Name"),
            'description' => __("The name for this remapping task."),
            'required' => true,
        ));
        // Source
        $this->addElement('select', 'task_source', array(
            'label' => __("Source"),
            'description' => __("The source image(s) for this remapping task."),
            'multiOptions' => get_table_options('Collection'),
            'required' => true,
        ));
        // Target
        $this->addElement('select', 'task_target', array(
            'label' => __("Target"),
            'description' => __("The target image(s) for this remapping task."),
            'multiOptions' => get_table_options('Collection'),
            'required' => true,
        ));
    }
}
