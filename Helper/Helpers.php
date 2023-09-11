<?php

namespace MelTheDev\MeiliSearch\Helper;

use MelTheDev\MeiliSearch\Exception\MissingObjectId;

class Helpers
{
    public static function ensureObjectID($objects, $message = 'ObjectID is required to add a record, a synonym or a query rule.')
    {
        // In case a single objects is passed
        if (isset($objects['objectID'])) {
            return;
        }

        // In case multiple objects are passed
        foreach ($objects as $object) {
            if (!isset($object['objectID']) && !isset($object['body']['objectID'])) {
                throw new MissingObjectId($message);
            }
        }
    }

    public static function buildBatch($items, $action)
    {
        return array_map(function ($item) use ($action) {
            return [
                'action' => $action,
                'body' => $item,
            ];
        }, $items);
    }
}
