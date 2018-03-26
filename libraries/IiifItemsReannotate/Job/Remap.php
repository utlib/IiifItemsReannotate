<?php

class IiifItemsReannotate_Job_Remap extends Omeka_Job_AbstractJob {
    private $_status , $_task, $_rootUrl;

    public function __construct(array $options) {
        parent::__construct($options);
        $this->_status = $options['statusId'];
        $this->_task = $options['taskId'];
        $this->_rootUrl = $options['rootUrl'];
    }

    protected function relerp($oldMin, $oldMax, $x, $newMin, $newMax) {
        return $newMin + (($x - $oldMin) * ($newMax - $newMin)) / ($oldMax - $oldMin);
    }

    protected function raw_iiif_metadata($record, $optionSlug) {
        if ($elementText = get_db()->getTable('ElementText')->findBySql('element_texts.element_id = ? AND element_texts.record_type = ? AND element_texts.record_id = ?', array(get_option($optionSlug), get_class($record), $record->id), true)) {
            return $elementText->text;
        } else {
            return '';
        }
    }

    protected function buildAnnotation($annoItem) {
        $elementTextTable = get_db()->getTable('ElementText');
        $currentAnnotationJson = json_decode($elementTextTable->findBySql("element_texts.element_id = ? AND element_texts.record_type = 'Item' AND element_texts.record_id = ?", array(
            get_option('iiifitems_item_json_element'),
            $annoItem->id,
        ), true), true);
        $currentText = $elementTextTable->findBySql("element_texts.element_id = ? AND element_texts.record_type = 'Item' AND element_texts.record_id = ?", array(
            get_option('iiifitems_annotation_text_element'),
            $annoItem->id,
        ), true);
        $currentAnnotationJson['resource'] = array(
            array(
                '@type' => 'dctypes:Text',
                'format' => $currentText->html ? 'text/html' : 'text/plain',
                'chars' => $currentText->text,
            ),
        );
        $currentAnnotationJson['@id'] = $this->_rootUrl . "/oa/items/{$annoItem->id}/anno.json";
        foreach ($annoItem->getTags() as $tag) {
            $currentAnnotationJson['resource'][] = array(
                '@type' => 'oa:Tag',
                'chars' => $tag->name,
            );
        }
        if ($attachedItem = IiifItems_Util_Annotation::findAnnotatedItemFor($annoItem)) {
            if (!($canvasId = raw_iiif_metadata($attachedItem, 'iiifitems_item_atid_element'))) {
                $canvasId = $this->_rootUrl . "/oa/items/{$attachedItem->id}/canvas.json";
            }
            $svgSelectors = IiifItems_Util_Annotation::getAnnotationSvg($annoItem);
            $xywhSelectors = IiifItems_Util_Annotation::getAnnotationXywh($annoItem);
            $svgSelector = empty($svgSelectors) ? null : $svgSelectors[0];
            $xywhSelector = empty($xywhSelectors) ? null : $xywhSelectors[0];
            if ($svgSelector || $xywhSelector) {
                unset($currentAnnotationJson['on']);
                // Mirador 2.3+ format
                if ($svgSelector && $xywhSelector) {
                    $currentAnnotationJson['on'] = array();
                    $areas = min(count($svgSelectors), count($xywhSelectors));
                    for ($i = 0; $i < $areas; $i++) {
                        $currentAnnotationJson['on'][] = array(
                            '@type' => 'oa:SpecificResource',
                            'full' => $canvasId,
                            'selector' => array(
                                '@type' => 'oa:Choice',
                                'default' => array(
                                    '@type' => 'oa:FragmentSelector',
                                    'value' => 'xywh=' . $xywhSelectors[$i],
                                ),
                                'item' => array(
                                    '@type' => 'oa:SvgSelector',
                                    'value' => $svgSelectors[$i],
                                ),
                            ),
                        );
                    }
                }
                // xywh-only format
                elseif ($xywhSelector) {
                    $currentAnnotationJson['on'] = $canvasId . '#xywh=' . $xywhSelector;
                }
                // Mirador 2.2- format
                else {
                    $currentAnnotationJson['on'] = array(
                        '@type' => 'oa:SpecificResource',
                        'full' => $canvasId,
                        'selector' => array(
                            '@type' => 'oa:SvgSelector',
                            'value' => $svgSelector,
                        ),
                    );
                }
            }
        }
        return $currentAnnotationJson;
    }

    public function perform() {
        // For each mapping
        try {
            $db = get_db();
            $status = $db->getTable('IiifItemsReannotate_Status')->find($this->_status);
            $task = $db->getTable('IiifItemsReannotate_Task')->find($this->_task);
            $status->status = __("In Progress");
            foreach ($db->getTable('IiifItemsReannotate_Mapping')->findBy(array('task_id' => array($task->id))) as $mapping) {
                debug("Mapping {$mapping->source_item_id} => {$mapping->target_item_id}");
                try {
                    // If it is passed
                    if ($mapping->target_item_id === null) {
                        // Increment skips
                        $status->skips++;
                    }
                    // If it is not passed
                    else {
                        $xsrc = $mapping->source_x;
                        $ysrc = $mapping->source_y;
                        $wsrc = $mapping->source_w;
                        $hsrc = $mapping->source_h;
                        $xtgt = $mapping->target_x;
                        $ytgt = $mapping->target_y;
                        $wtgt = $mapping->target_w;
                        $htgt = $mapping->target_h;
                        $sourceItem = $mapping->getSourceItem();
                        if ($sourceItemJsonStr = $this->raw_iiif_metadata($sourceItem, 'iiifitems_item_json_element')) {
                            $cnv = json_decode($sourceItemJsonStr, true);
                            $wori = $cnv['width'];
                            $hori = $cnv['height'];
                        } else {
                            list($wori, $hori) = getimagesize(FILES_DIR . DIRECTORY_SEPARATOR . $sourceItem->getFile()->getStoragePath());
                        }

                        $destinationItem = $mapping->getTargetItem();
                        if ($destinationItemJsonStr = $this->raw_iiif_metadata($destinationItem, 'iiifitems_item_json_element')) {
                            $cnv = json_decode($destinationItemJsonStr, true);
                            $wdest = $cnv['width'];
                            $hdest = $cnv['height'];
                        } else {
                            list($wdest, $hdest) = getimagesize(FILES_DIR . DIRECTORY_SEPARATOR . $destinationItem->getFile()->getStoragePath());
                        }
                        $matrix = array($wtgt / $wsrc, 0, 0, $htgt / $hsrc, ($xtgt/$wtgt - $xsrc/$wsrc) *  $wtgt, ($ytgt/$htgt - $ysrc/$hsrc) * $htgt);

                        debug("Found " . count(IiifItems_Util_Annotation::findAnnotationItemsUnder($mapping->getSourceItem())) . " annotations to map");
                        foreach (IiifItems_Util_Annotation::findAnnotationItemsUnder($mapping->getSourceItem()) as $oldAnnotation) {
                            // Calculate the new xywh bounds (if applicable) using linear reinterpolation
                            debug("Processing XYWH...");
                            $oldXywhBounds = IiifItems_Util_Annotation::getAnnotationXywh($oldAnnotation, true);
                            $newXywhBounds = array();
                            foreach ($oldXywhBounds as $oldXywhBound) {
                                $newXywhBounds[] = array(
                                    round($this->relerp($xsrc, $xsrc + $wsrc, $oldXywhBound[0], $xtgt, $xtgt + $wtgt)),
                                    round($this->relerp($ysrc, $ysrc + $hsrc, $oldXywhBound[1], $ytgt, $ytgt + $htgt)),
                                    round($this->relerp(0, $wsrc, $oldXywhBound[2], 0, $wtgt)),
                                    round($this->relerp(0, $hsrc, $oldXywhBound[3], 0, $htgt)),
                                );
                            }
                            debug("Done processing " . count($oldXywhBounds) . ".");
                            // Calculate the new SVG bounds (if applicable) by changing the path's transform
                            debug("Processing SVG");
                            $oldSvgBounds = IiifItems_Util_Annotation::getAnnotationSvg($oldAnnotation);
                            $newSvgBounds = array();
                            foreach ($oldSvgBounds as $oldSvgBound) {
                                $oldSvgBound = str_replace("<svg xmlns='http://www.w3.org/2000/svg'>", '<svg xmlns="http://www.w3.org/2000/svg">', $oldSvgBound);
                                $sxe = simplexml_load_string($oldSvgBound);
                                if ($sxe !== false) {
                                    foreach ($sxe->path as $path) {
                                        if (isset($path['transform'])) {
                                            $path['transform'] .= ' matrix(' . join(',', $matrix) . ')';
                                        } else {
                                            $path['transform'] = 'matrix(' . join(',', $matrix) . ')';
                                        }
                                        $newSvgBound = str_replace(array("<?xml version=\"1.0\"?>", "\n"), array('', ''), $sxe->asXML());
                                        $newSvgBounds[] = $newSvgBound;
                                    }
                                }
                            }
                            debug("Done processing " . count($oldSvgBounds) . ".");
                            // Start item metadata
                            $itemProperties = array(
                                'public' => $oldAnnotation->public,
                                'item_type_id' => get_option('iiifitems_annotation_item_type'),
                            );
                            $itemMetadata = array(
                                'Dublin Core' => array(
                                    'Title' => array(),
                                ),
                                'Item Type Metadata' => array(
                                    'On Canvas' => array(),
                                    'Selector' => array(),
                                    'Text' => array(),
                                    'Annotated Region' => array(),
                                ),
                                'IIIF Item Metadata' => array(
                                    'Original @id' => array(array('text' => 'TBD', 'html' => false)),
                                    'JSON Data' => array(),
                                ),
                            );
                            // Attachment is the mapping's target item
                            $itemMetadata['Item Type Metadata']['On Canvas'][] = array('text' => $this->raw_iiif_metadata($mapping->getTargetItem(), 'iiifitems_item_uuid_element'), 'html' => false);
                            // Set xywh to new bounds
                            foreach ($newXywhBounds as $newXywhBound) {
                                $itemMetadata['Item Type Metadata']['Annotated Region'][] = array('text' => join(',', $newXywhBound), 'html' => false);
                            }
                            // Set svg to new bounds
                            foreach ($newSvgBounds as $newSvgBound) {
                                $itemMetadata['Item Type Metadata']['Selector'][] = array('text' => $newSvgBound, 'html' => false);
                            }
                            // Carry over title, text and JSON
                            foreach ($oldAnnotation->getElementTexts('Dublin Core', 'Title') as $elementText) {
                                $itemMetadata['Dublin Core']['Title'][] = array('text' => $elementText->text, 'html' => $elementText->html);
                            }
                            foreach ($oldAnnotation->getElementTextsByRecord(get_record_by_id('Element', get_option('iiifitems_annotation_text_element'))) as $elementText) {
                                $itemMetadata['Item Type Metadata']['Text'][] = array('text' => $elementText->text, 'html' => $elementText->html);
                            }
                            foreach ($oldAnnotation->getElementTextsByRecord(get_record_by_id('Element', get_option('iiifitems_item_json_element'))) as $elementText) {
                                $itemMetadata['IIIF Item Metadata']['JSON Data'][] = array('text' => $elementText->text, 'html' => $elementText->html);
                            }
                            // Save the new item
                            $newItem = insert_item($itemProperties, $itemMetadata);
                            $newItemJson = $this->buildAnnotation($newItem);
                            $jsonElementText = $newItem->getElementTextsByRecord(get_record_by_id('Element', get_option('iiifitems_item_json_element')))[0];
                            $jsonElementText->text = json_encode($newItemJson, JSON_UNESCAPED_SLASHES);
                            $jsonElementText->save();
                            $originalIdText = $newItem->getElementTextsByRecord(get_record_by_id('Element', get_option('iiifitems_item_atid_element')))[0];
                            $originalIdText->text = $newItemJson['@id'];
                            $originalIdText->save();
                        }
                        // Increment dones
                        $status->dones++;
                    }
                }
                // Catch exceptions
                catch (Exception $ex) {
                    // Increment fails and log
                    debug('Mapping ' . $mapping->source_item_id . ' -> ' . $mapping->target_item_id . " failed. " . $ex->getMessage() . "\nTrace\n" . $ex->getTraceAsString());
                    $status->fails++;
                }
                // Update progress
                $status->progress++;
                $status->save();
            }
            // Done
            $status->status = __("Completed");
            $status->save();
        } catch (Exception $ex) {
            debug("Remap Task failed. Trace\n" . $ex->getTraceAsString());
            $status->status = __("Failed");
            $status->save();
        }
    }
}
