<?php

/**
 * Main CRUD controller for tasks.
 * @package controllers
 */
class IiifItemsReannotate_TasksController extends IiifItemsReannotate_Application_AbstractActionController {
    /**
     * Number of task records per page.
     * @var int
     */
    protected $_browseRecordsPerPage = self::RECORDS_PER_PAGE_SETTING;
    
    /**
     * Mark the default model type for this controller.
     */
    public function init() {
        $this->_helper->db->setDefaultModelName('IiifItemsReannotate_Task');     
    }
    
    /**
     * The task listings path.
     */
    public function indexAction() {
        $this->restrictVerb('GET');
        $db = $this->_helper->db;
        $table = $db->getTable('IiifItemsReannotate_Task');
        $sortField = $this->_getParam('sort_field') ? $this->_getParam('sort_field') : 'created';
        $sortOrder = $this->_getParam('sort_dir') ? (($this->_getParam('sort_dir') == 'd') ? 'DESC' : 'ASC') : 'DESC';
        $select = $table->getSelectForFindBy();
        $recordsPerPage = $this->_getBrowseRecordsPerPage();
        $currentPage = $this->getParam('page', 1);
        $this->_helper->db->applySorting($select, $sortField, $sortOrder);
        $this->_helper->db->applyPagination($select, $recordsPerPage, $currentPage);
        $this->view->tasks = $table->fetchObjects($select);
        // Add pagination data to the registry. Used by pagination_links().
        if ($recordsPerPage) {
            Zend_Registry::set('pagination', array(
                'page' => $currentPage, 
                'per_page' => $recordsPerPage, 
                'total_results' => $table->count(), 
            ));
        }
    }
    
    /**
     * Path for creating a new task.
     */
    public function newAction() {
        // GET = Go to form / POST = Process form data
        $this->restrictVerb(array('GET', 'POST'));
        
        // POST: Process form data
        if ($this->getRequest()->isPost()) {
            $form = new IiifItemsReannotate_Form_NewTask();
            if (!$form->isValid($_POST)) {
                // Form data is invalid, redirect back
                $this->_helper->_flashMessenger(__('There was an error on the form. Please try again.'), 'error');
                return;
            } else {
                // Form data is valid, redirect to edit
                $db = get_db();
                $newTaskId = $db->insert("IiifItemsReannotate_Task", array(
                    'name' => $this->getParam('task_name'),
                    'source_collection_id' => $this->getParam('task_source'),
                    'target_collection_id' => $this->getParam('task_target'),
                ));
                Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->gotoUrl(absolute_url(array('id' => $newTaskId), 'IiifItemsReannotate_Tasks_Edit'));
                return;
            }
        }
    }
    
    /**
     * Path for working on a task.
     * @throws Omeka_Controller_Exception_404
     */
    public function editAction() {
        // Sanity check: Must be GET or POST, and the task must exist
        $this->restrictVerb(array('GET', 'POST'));
        if (!($task = get_db()->getTable('IiifItemsReannotate_Task')->find($this->getParam('id')))) {
            throw new Omeka_Controller_Exception_404;
        }
        
        // POST: Check submission, then start the job if it's OK
        if ($this->getRequest()->isPost()) {
            // Check that the set of mappings is complete
            if ($task->countMappings() != $task->countMaxMappings()) {
                $this->_helper->_flashMessenger(__('There are still unmapped source canvases. Please map or pass them and retry.'), 'error');
                return;
            }
            // Start the job
            $newJobStatusId = get_db()->insert('IiifItemsReannotate_Status', array(
                'source' => $task->name,
                'dones' => 0,
                'skips' => 0,
                'fails' => 0,
                'status' => 'Queued',
                'total' => $task->countMappings(),
                'added' => date('Y-m-d H:i:s'),
            ));
            $serverUrlHelper = new Zend_View_Helper_ServerUrl;
            Zend_Registry::get('bootstrap')->getResource('jobs')->sendLongRunning('IiifItemsReannotate_Job_Remap', array(
                'statusId' => $newJobStatusId,
                'taskId' => $task->id,
                'rootUrl' => $serverUrlHelper->serverUrl(),
            ));
            // Redirect to the status screen
            Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->gotoUrl(absolute_url(array(), 'IiifItemsReannotate_Status'));
            return;
        }
        
        // Standard view locals
        $this->view->task = $task;
        $this->view->sourceCollection = $task->getSourceCollection();
        $this->view->sourceItems = get_db()->getTable('Item')->findBy(array('collection_id' => $task->getSourceCollection()->id));
        $sourceItemIds = array();
        foreach ($this->view->sourceItems as $sourceItem) {
            $sourceItemIds[] = $sourceItem->id;
        }
        $this->view->sourceItemIds = $sourceItemIds;
        $this->view->targetCollection = $task->getTargetCollection();
        $this->view->destinationItems = get_db()->getTable('Item')->findBy(array('collection_id' => $task->getTargetCollection()->id));
        $destinationItemIds = array();
        foreach ($this->view->destinationItems as $destinationItem) {
            $destinationItemIds[] = $destinationItem->id;
        }
        $this->view->destinationItemIds = $destinationItemIds;
        $jumpToSource = $task->getTodoSourceItem();
        $this->view->todoSourceItem = $jumpToSource;
        $jumpToTarget = $task->getTodoTargetItem();
        $this->view->todoTargetItem = $jumpToTarget;
    }
        
    /**
     * Processing path for deleting a task.
     * @throws Omeka_Controller_Exception_404
     */
    public function deleteAction() {
        // Sanity check
        $this->restrictVerb('POST');
        if (!($task = get_db()->getTable('IiifItemsReannotate_Task')->find($this->getParam('id')))) {
            throw new Omeka_Controller_Exception_404;
        }
        
        // Delete the task and show notification
        $task->delete();
        $this->_helper->_flashMessenger(__('Task deleted.'), 'success');
        Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->gotoUrl(absolute_url(array(), 'IiifItemsReannotate_Tasks'));
        return;
    }
}
