<?php

/**
 * The status of a remap job.
 * @package models
 */
class IiifItemsReannotate_Status extends Omeka_Record_AbstractRecord {
    /**
     * The database ID.
     * @var int
     */
    public $id;

    /**
     * Name for the source of this status.
     * @var string
     */
    public $source;

    /**
     * The number of successful mappings.
     * @var int
     */
    public $dones;

    /**
     * The number of skipped mappings.
     * @var int
     */
    public $skips;

    /**
     * The number of failed mappings.
     * @var int
     */
    public $fails;

    /**
     * The status of the mapping job.
     * @var string
     */
    public $status;

    /**
     * Total number of carried out mappings, successful or not.
     * @var int
     */
    public $progress;

    /**
     * Total number of mappings scheduled for this job.
     * @var int
     */
    public $total;

    /**
     * The time that this status was first updated.
     * @var datetime
     */
    public $added;

    /**
     * The time that this status was last updated.
     * @var datetime
     */
    public $modified;
}
