<?php

/**
 * A single mapping task.
 * @package models
 */
class IiifItemsReannotate_Task extends Omeka_Record_AbstractRecord {
    /**
     * The database ID.
     * @var int
     */
    public $id;
    
    /**
     * The name for this remapping task.
     * @var string
     */
    public $name;
    
    /**
     * The ID of the source collection.
     * @var int
     */
    public $source_collection_id;
    
    /**
     * The ID of the target collection.
     * @var int
     */
    public $target_collection_id;
    
    /**
     * The time when the task was created.
     * @var datetime
     */
    public $created;
    
    /**
     * Return the source collection.
     * @return Collection
     */
    public function getSourceCollection() {
        return get_record_by_id('Collection', $this->source_collection_id);
    }
    
    /**
     * Return the target collection.
     * @return Collection
     */
    public function getTargetCollection() {
        return get_record_by_id('Collection', $this->target_collection_id);
    }
    
    /**
     * Return the first item from the source that has not been mapped or passed.
     * @return Item
     */
    public function getTodoSourceItem() {
        $db = get_db();
        $itemTable = $db->getTable('Item');
        $select = $itemTable->getSelectForFindBy();
        $select->where("items.collection_id = ?", array($this->source_collection_id));
        $select->where("items.id NOT IN (SELECT source_item_id FROM {$db->prefix}iiif_items_reannotate_mappings WHERE task_id = ?)", array($this->id));
        $jumpTo = $itemTable->fetchObject($select);
        if ($jumpTo) {
            return $jumpTo;
        } else {
            $select = $itemTable->getSelectForFindBy();
            $select->where("items.collection_id = ?", array($this->source_collection_id));
            return $itemTable->fetchObject($select);
        }
    }
    
    /**
     * Return the first item from the target that has not been mapped or passed.
     * @return Item
     */
    public function getTodoTargetItem() {
        $db = get_db();
        $itemTable = $db->getTable('Item');
        $select = $itemTable->getSelectForFindBy();
        $select->where("items.collection_id = ?", array($this->target_collection_id));
        $select->where("items.id NOT IN (SELECT target_item_id FROM {$db->prefix}iiif_items_reannotate_mappings WHERE task_id = ?)", array($this->id));
        $jumpTo = $itemTable->fetchObject($select);
        if ($jumpTo) {
            return $jumpTo;
        } else {
            $select = $itemTable->getSelectForFindBy();
            $select->where("items.collection_id = ?", array($this->target_collection_id));
            return $itemTable->fetchObject($select);
        }
    }
    
    /**
     * Count the number of mappings/passes that this task already has.
     * @return int
     */
    public function countMappings() {
        return get_db()->getTable('IiifItemsReannotate_Mapping')->count(array('task_id' => $this->id));
    }
    
    /**
     * Count the number of mappings/passes that this task can have when fully mapped.
     * @return int
     */
    public function countMaxMappings() {
        return $this->getSourceCollection()->totalItems();
    }
}
