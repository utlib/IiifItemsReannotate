<?php

/**
 * Main CRUD controller for the status panel.
 * @package controllers
 */
class IiifItemsReannotate_StatusController extends IiifItemsReannotate_Application_AbstractActionController {
    /**
     * Number of status records per page.
     * @var int
     */
    protected $_browseRecordsPerPage = self::RECORDS_PER_PAGE_SETTING;
    
    /**
     * Mark the default model type for this controller.
     */
    public function init() {
        $this->_helper->db->setDefaultModelName('IiifItemsReannotate_Status');     
    }
    
    /**
     * The status listings path.
     */
    public function indexAction() {
        $this->restrictVerb('GET');
        $db = $this->_helper->db;
        $table = $db->getTable('IiifItemsReannotate_Status');
        $sortField = $this->_getParam('sort_field') ? $this->_getParam('sort_field') : 'added';
        $sortOrder = $this->_getParam('sort_dir') ? (($this->_getParam('sort_dir') == 'd') ? 'DESC' : 'ASC') : 'DESC';
        $select = $table->getSelectForFindBy();
        $recordsPerPage = $this->_getBrowseRecordsPerPage();
        $currentPage = $this->getParam('page', 1);
        $this->_helper->db->applySorting($select, $sortField, $sortOrder);
        $this->_helper->db->applyPagination($select, $recordsPerPage, $currentPage);
        $this->view->statuses = $table->fetchObjects($select);
        // Add pagination data to the registry. Used by pagination_links().
        if ($recordsPerPage) {
            Zend_Registry::set('pagination', array(
                'page' => $currentPage, 
                'per_page' => $recordsPerPage, 
                'total_results' => $table->count(), 
            ));
        }
    }
    
    public function ajaxStatusAction() {
        $this->restrictVerb('GET');
        $jsonData = array();
        if ($ids = $this->_getParam('id')) {
            $statuses = $this->_helper->db->getTable('IiifItemsReannotate_Status')->findBy(array('id' => $ids));
            foreach ($statuses as $status) {
                $jsonData[] = array(
                    'id' => $status->id,
                    'dones' => $status->dones,
                    'skips' => $status->skips,
                    'fails' => $status->fails,
                    'status' => $status->status,
                );
            }
        }
        $this->respondWithJson($jsonData, 200);
    }
}
