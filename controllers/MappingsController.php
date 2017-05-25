<?php

class IiifItemsReannotate_MappingsController extends IiifItemsReannotate_Application_AbstractActionController {
    public function confirmAction() {
        $this->restrictVerb('POST');
        if (!($task = get_db()->getTable('IiifItemsReannotate_Task')->find($this->getParam('id')))) {
            $this->respondWithJson(array('error' => $this->getParam('id')), 404);
            return false;
        }
        try {
            $myParams = array(
                'task_id' => $task->id,
                'source_item_id' => $this->getParam('src'),
                'target_item_id' => $this->getParam('tgt'),
                'source_x' => $this->getParam('sx'),
                'source_y' => $this->getParam('sy'),
                'source_w' => $this->getParam('sw'),
                'source_h' => $this->getParam('sh'),
                'target_x' => $this->getParam('tx'),
                'target_y' => $this->getParam('ty'),
                'target_w' => $this->getParam('tw'),
                'target_h' => $this->getParam('th'),
            );
            if ($newMapping = get_db()->getTable('IiifItemsReannotate_Mapping')->findBySql('task_id = ? AND source_item_id = ?', array($task->id, $this->getParam('src')), true)) {
                $newMapping->setArray($myParams);
                $newMapping->save();
            } else {
                $newMappingId = get_db()->insert('IiifItemsReannotate_Mappings', $myParams);
                $newMapping = get_db()->getTable('IiifItemsReannotate_Mapping')->find($newMappingId);
            }
            $nextMapping = get_db()->getTable('IiifItemsReannotate_Mapping')->findBySql('task_id = ? AND source_item_id = ?', array($task->id, $this->getParam('next')), true);
            $this->respondWithJson($this->_templateReply($task, null, $nextMapping));
            return true;
        } catch (Exception $ex) {
            $trace = $ex->getMessage();
            debug("Exception in Annotation Remapper: " . $trace);
            $this->respondWithJson(array('error' => $trace), 500);
            return false;
        }
    }
    
    public function passAction() {
        $this->restrictVerb('POST');
        if (!($task = get_db()->getTable('IiifItemsReannotate_Task')->find($this->getParam('id')))) {
            $this->respondWithJson(array('error' => $this->getParam('id')), 404);
            return false;
        }
        try {
            $myParams = array(
                'task_id' => $task->id,
                'source_item_id' => $this->getParam('src'),
                'target_item_id' => null,
                'source_x' => 0,
                'source_y' => 0,
                'source_w' => 0,
                'source_h' => 0,
                'target_x' => 0,
                'target_y' => 0,
                'target_w' => 0,
                'target_h' => 0,
            );
            if ($newMapping = get_db()->getTable('IiifItemsReannotate_Mapping')->findBySql('task_id = ? AND source_item_id = ?', array($task->id, $this->getParam('src')), true)) {
                $newMapping->setArray($myParams);
                $newMapping->save();
            } else {
                $newMappingId = get_db()->insert('IiifItemsReannotate_Mapping', $myParams);
                $newMapping = get_db()->getTable('IiifItemsReannotate_Mapping')->find($newMappingId);
            }
            $nextMapping = get_db()->getTable('IiifItemsReannotate_Mapping')->findBySql('task_id = ? AND source_item_id = ?', array($task->id, $this->getParam('next')), true);
            $this->respondWithJson($this->_templateReply($task, null, $nextMapping));
            return true;
        } catch (Exception $ex) {
            $trace = $ex->getTraceAsString();
            debug("Exception in Annotation Remapper: " . $trace);
            $this->respondWithJson(array('error' => $trace), 500);
            return false;
        }
    }
    
    public function resetAction() {
        $this->restrictVerb('POST');
        if (!($task = get_db()->getTable('IiifItemsReannotate_Task')->find($this->getParam('id')))) {
            $this->respondWithJson(array('error' => $this->getParam('id')), 404);
            return false;
        }
        try {
            $mappingToDelete = get_db()->getTable('IiifItemsReannotate_Mapping')->findBySql('source_item_id = ?', array($this->getParam('src')), true);
            if ($mappingToDelete) {
                $mappingToDelete->delete();
            }
            $maps = $task->countMappings();
            $maxMaps = $task->countMaxMappings();
            $this->respondWithJson(array(
                'maps' => $maps,
                'maxMaps' => $maxMaps,
                'enableRun' => $maps == $maxMaps,
            ));
            return true;
        } catch (Exception $ex) {
            $trace = $ex->getTraceAsString();
            debug("Exception in Annotation Remapper: " . $trace);
            $this->respondWithJson(array('error' => $trace), 500);
            return false;
        }
    }
    
    public function infoAction() {
        $this->restrictVerb('GET');
        if (($task = get_db()->getTable('IiifItemsReannotate_Task')->find($this->getParam('id'))) === null || ($srcId = $this->getParam('src')) === null) {
            $this->respondWithJson(array('error' => $this->getParam('id')), 404);
            return false;
        }
        try {
            $mapping = get_db()->getTable('IiifItemsReannotate_Mapping')->findBySql('task_id = ? AND source_item_id = ?', array($task->id, $srcId), true);
            $this->respondWithJson($this->_templateReply($task, $mapping));
            return true;
        } catch (Exception $ex) {
            $trace = $ex->getTraceAsString();
            debug("Exception in Annotation Remapper: " . $trace);
            $this->respondWithJson(array('error' => $trace), 500);
            return false;
        }
    }
    
    protected function _templateReply($task, $mapping=null, $nextMapping=null) {
        $maps = $task->countMappings();
        $maxMaps = $task->countMaxMappings();
        if ($mapping === null) {
            $mapSource = null;
            $mapTarget = null;
        } else {
            if ($mapping->target_item_id === null) {
                $mapSource = array(
                    'id' => $mapping->source_item_id, 
                    'existing' => false,
                );
                $mapTarget = null;
            } else {
                $mapSource = array(
                    'id' => $mapping->source_item_id, 
                    'existing' => true,
                    'xywh' => array($mapping->source_x, $mapping->source_y, $mapping->source_w, $mapping->source_h)
                );
                $mapTarget = array(
                    'id' => $mapping->target_item_id, 
                    'existing' => true,
                    'xywh' => array($mapping->target_x, $mapping->target_y, $mapping->target_w, $mapping->target_h)
                );
            }
        }
        if ($nextMapping === null) {
            $nextMapSource = null;
            $nextMapTarget = null;
        } else {
            if ($nextMapping->target_item_id === null) {
                $nextMapSource = array(
                    'id' => $nextMapping->source_item_id, 
                    'existing' => false,
                );
                $nextMapTarget = null;
            } else {
                $nextMapSource = array(
                    'id' => $nextMapping->source_item_id, 
                    'existing' => true,
                    'xywh' => array($nextMapping->source_x, $nextMapping->source_y, $nextMapping->source_w, $nextMapping->source_h)
                );
                $nextMapTarget = array(
                    'id' => $nextMapping->target_item_id, 
                    'existing' => true,
                    'xywh' => array($nextMapping->target_x, $nextMapping->target_y, $nextMapping->target_w, $nextMapping->target_h)
                );
            }
        }
        if ($nextMapping === null) {
            return array(
                'maps' => $maps,
                'maxMaps' => $maxMaps,
                'mapSource' => $mapSource,
                'mapTarget' => $mapTarget,
                'enableRun' => $maps == $maxMaps,
            );
        } else {
            return array(
                'maps' => $maps,
                'maxMaps' => $maxMaps,
                'mapSource' => $mapSource,
                'mapTarget' => $mapTarget,
                'nextMapSource' => $nextMapSource,
                'nextMapTarget' => $nextMapTarget,
                'enableRun' => $maps == $maxMaps,
            );
        }
    }
}
