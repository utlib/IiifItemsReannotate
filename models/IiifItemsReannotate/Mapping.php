<?php

/**
 * A source-target area pair.
 * @package models
 */
class IiifItemsReannotate_Mapping extends Omeka_Record_AbstractRecord {
    /**
     * The database ID.
     * @var int
     */
    public $id;

    /**
     * The ID of the task that this mapping belongs to.
     * @var int
     */
    public $task_id;

    /**
     * The ID of the item where the source area is.
     * @var int
     */
    public $source_item_id;

    /**
     * The ID of the item where the target area is.
     * @var int
     */
    public $target_item_id;

    /**
     * The source area's top-left X coordinate.
     * @var int
     */
    public $source_x;

    /**
     * The source area's top-left Y coordinate.
     * @var int
     */
    public $source_y;

    /**
     * The source area's width.
     * @var int
     */
    public $source_w;

    /**
     * The source area's height.
     * @var int
     */
    public $source_h;

    /**
     * The target area's top-left X coordinate.
     * @var int
     */
    public $target_x;

    /**
     * The target area's top-left Y coordinate.
     * @var int
     */
    public $target_y;

    /**
     * The target area's width.
     * @var int
     */
    public $target_w;

    /**
     * The target area's height.
     * @var int
     */
    public $target_h;

    /**
     * Return the task that this mapping is part of.
     * @return IiifItemsReannotate_Task
     */
    public function getTask() {
        return get_record_by_id('IiifItemsReannotate_Task', $this->task_id);
    }

    /**
     * Return the item that the source area is from.
     * @return Item
     */
    public function getSourceItem() {
        return get_record_by_id('Item', $this->source_item_id);
    }

    /**
     * Return the item that the target area is from.
     * @return Item
     */
    public function getTargetItem() {
        return is_numeric($this->target_item_id) ? get_record_by_id('Item', $this->target_item_id) : null;
    }
}