<?php

/**
 * Main CRUD controller for the cropper.
 * @package controllers
 */
class IiifItemsReannotate_CropperController extends IiifItemsReannotate_Application_AbstractActionController {
    /**
     * Display a cropper for the given item.
     * @throws Omeka_Controller_Exception_404
     */
    public function cropperAction() {
        // Sanity check: Must be through get, item and task must exist
        $this->restrictVerb('GET');
        if (!($item = get_record_by_id('Item', $this->getParam('id')))) {
            throw new Omeka_Controller_Exception_404;
        }
        $task = null;
        $side = $this->getParam('side');
        if (isset($_GET['task']) && !($task = get_db()->getTable('IiifItemsReannotate_Task')->find($this->getParam('task'))) && array_search($side, array('s', 't', '')) === false) {
            throw new Omeka_Controller_Exception_404;
        }
        
        // Display the correct coloured selector frame
        switch ($this->getParam('status')) {
            case 'confirmed': $selectorColour = 'green'; break;
            case 'passed': $selectorColour = 'red'; break;
            case '': $selectorColour = 'white'; break;
            default: throw new Omeka_Controller_Exception_404;
        }
        
        // Find the mapping and set the selector frame to its specified region
        $mapping = $task ? get_db()->getTable('IiifItemsReannotate_Mapping')->findBySql('task_id = ? AND ' . (($side == 's') ? 'source_item_id = ?' : 'target_item_id = ?'), array($task->id, $item->id), true) : null;
        $this->view->item = $item;
        $canvas = IiifItems_Util_Canvas::buildCanvas($item, 'asdf', false);
        $tileSources = array();
        foreach ($canvas['images'] as $image) {
            $tileSources[] = $image['resource']['service']['@id'] . '/info.json';
        }
        $this->view->tileSources = $tileSources;
        $xywh = (isset($_GET['xywh'])) ? explode(',', $this->getParam('xywh'))
            : (($mapping && $side == 's') ? array($mapping->source_x, $mapping->source_y, $mapping->source_w, $mapping->source_h)
            : (($mapping && $side == 't') ? array($mapping->target_x, $mapping->target_y, $mapping->target_w, $mapping->target_h)
            : array(0, 0, $canvas['width'], $canvas['height'])));
        $this->view->xywh = $xywh;
        $this->view->status = $this->getParam('status');
        $this->view->selectorColour = $selectorColour;
    }
}
