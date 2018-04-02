<?php

/**
 * Base controller for the plugin.
 * @package IiifItemsReannotate/Application
 */
abstract class IiifItemsReannotate_Application_AbstractActionController extends Omeka_Controller_AbstractActionController {
    /**
     * Return a database selector for the given model, with current pagination parameters.
     *
     * @param string $modelName Name of the model
     * @return Omeka_Db_Select
     */
    protected function getPaginatedModelSelect($modelName) {
        $table = $this->_helper->db->getTable($modelName);
        $sortField = $this->_getParam('sort_field') ? $this->getParam('sort_field') : 'added';
        $sortOrder = ($this->_getParam('sort_dir') ? (($this->getParam('sort_dir') == 'd') ? 'DESC' : 'ASC') : 'ASC');
        $select = $table->getSelectForFindBy();
        $recordsPerPage = $this->_getBrowseRecordsPerPage();
        $currentPage = $this->getParam('page', 1);
        $this->_helper->db->applySorting($select, $sortField, $sortOrder);
        $this->_helper->db->applyPagination($select, $recordsPerPage, $currentPage);
        return $select;
    }

    /**
     * Return a list of models with current pagination parameters.
     *
     * @param string $modelName Name of the model
     * @return Omeka_Record_AbstractRecord[]
     */
    protected function getPaginatedModels($modelName) {
        $select = $this->getPaginatedModelSelect($modelName);
        return $this->_helper->db->getTable($modelName)->fetchObjects($select);
    }

    /**
     * Respond with JSON data (no layout).
     *
     * @param array $jsonData JSON data in nested array form
     * @param integer $status The HTTP response code
     */
    protected function respondWithJson($jsonData, $status=200) {
        $response = $this->getResponse();
        $this->_helper->viewRenderer->setNoRender();
        $response->setHttpResponseCode($status);
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Content-Type', 'application/json');
        $response->clearBody();
        $response->setBody($this->json_encode($jsonData));
    }

    /**
     * Respond with raw data.
     *
     * @param string $data Response data
     * @param integer $status The HTTP response code
     * @param string $mime The MIME type
     */
    protected function respondWithRaw($data, $status=200, $mime='application/json') {
        $response = $this->getResponse();
        $this->_helper->viewRenderer->setNoRender();
        $response->setHttpResponseCode($status);
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Content-Type', $mime);
        $response->clearBody();
        $response->setBody($data);
    }

    /**
     * Encodes the argument in JSON.
     * Adds the unescaped slashes and unicode argument on PHP 5.4.0+
     *
     * @param mixed $mixed
     * @return array
     */
    protected function json_encode($mixed) {
        return version_compare(phpversion(), '5.4.0', '<')
            ? \json_encode($mixed)
            : \json_encode($mixed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Decodes the given JSON string to an associative array.
     *
     * @param string $str
     * @return array
     */
    protected function json_decode($str) {
        return \json_decode($str, false);
    }

    /**
     * Call this at the beginning of a controller action method to block unauthenticated users.
     *
     * @throws Omeka_Controller_Exception_404
     */
    protected function blockPublic() {
        if (!is_admin_theme()) {
            throw new Omeka_Controller_Exception_404;
        }
    }

    /**
     * Call this at the beginning of a controller action to block requests not made with the specified verb.
     *
     * @param string|string[] $verbAllowed The allowed HTTP verb(s)
     * @throws Omeka_Controller_Exception_404
     */
    protected function restrictVerb($verbAllowed) {
        $verb = $this->getRequest()->getMethod();
        if (is_array($verbAllowed)) {
            if (array_search(strtolower($verb), array_map('strtolower', $verbAllowed)) === false) {
                throw new Omeka_Controller_Exception_404;
            }
        } else {
            if (strtolower($verb) != strtolower($verbAllowed)) {
                throw new Omeka_Controller_Exception_404;
            }
        }
    }
}
