<?php

namespace MelTheDev\MeiliSearch\Helper;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use MelTheDev\MeiliSearch\Model\Client\SearchIndex;
use MelTheDev\MeiliSearch\Model\ObjectIterator;
use Symfony\Component\Console\Output\ConsoleOutput;

class MeiliSearchHelper
{
    /** @var int */
    private $maxRecordSize;
    /** @var array */
    private $potentiallyLongAttributes = ['description', 'short_description', 'meta_description', 'content'];
    /** @var array */
    private $nonCastableAttributes = ['sku', 'name', 'description', 'query'];
    /** @var string */
    private static $lastUsedIndexName;
    /** @var string */
    private static $lastTaskId;
    /** @var ConfigHelper */
    private ConfigHelper $config;
    /** @var SearchIndex */
    private SearchIndex $searchIndex;
    /** @var ConsoleOutput */
    private ConsoleOutput $consoleOutput;
    /** @var ManagerInterface */
    private ManagerInterface $messageManager;
    /** @var RequestInterface */
    private RequestInterface $request;

    /**
     * MeiliSearchHelper constructor.
     * @param ConsoleOutput $consoleOutput
     * @param ManagerInterface $messageManager
     * @param ConfigHelper $config
     * @param SearchIndex $searchIndex
     * @param RequestInterface $request
     */
    public function __construct(
        ConsoleOutput    $consoleOutput,
        ManagerInterface $messageManager,
        ConfigHelper     $config,
        SearchIndex      $searchIndex,
        RequestInterface $request
    ) {
        $this->consoleOutput  = $consoleOutput;
        $this->messageManager = $messageManager;
        $this->config         = $config;
        $this->searchIndex    = $searchIndex;
        $this->request        = $request;
    }

    public function getIndex(string $indexName)
    {
        return $this->searchIndex->setIndexName($indexName);
    }

    /**
     * Set settings
     *
     * @param string $indexName
     * @param array $settings
     * @param bool $forwardToReplicas
     * @param bool $mergeSettings
     * @param string $mergeSettingsFrom
     *
     * @throws \Exception
     */
    public function setSettings(
        $indexName,
        $settings,
        $forwardToReplicas = false,
        $mergeSettings = false,
        $mergeSettingsFrom = ''
    ) {
        if ($mergeSettings === true) {
            $settings = $this->mergeSettings($indexName, $settings, $mergeSettingsFrom);
        }
        $this->searchIndex
            ->setIndexName($indexName)
            ->setSettings($settings);
    }

    /**
     * Add objects to MeiliSearch
     *
     * @param array $objects
     * @param string $indexName
     * @return void
     * @throws \MelTheDev\MeiliSearch\Exception\MissingObjectId
     */
    public function addObjects($objects, $indexName)
    {
        $this->prepareRecords($objects, $indexName);
        //do the stuffs sending data to MeiliSearch
        $this->searchIndex->setIndexName($indexName)->saveObjects(
            $objects,
            ['autoGenerateObjectIDIfNotExist' => true]
        );
    }

    /**
     * Merge settings
     *
     * @param string $indexName
     * @param array $settings
     * @param $mergeSettingsFrom
     * @return array
     */
    public function mergeSettings($indexName, $settings, $mergeSettingsFrom = '')
    {
        $onlineSettings = [];

        try {
            $sourceIndex = $indexName;
            if ($mergeSettingsFrom !== '') {
                $sourceIndex = $mergeSettingsFrom;
            }

            $onlineSettings = $this->getSettings($sourceIndex);
        } catch (\Exception $e) {
        }
        $removes = ['slaves', 'replicas'];

        if (isset($settings['attributesToIndex'])) {
            $settings['searchableAttributes'] = $settings['attributesToIndex'];
            unset($settings['attributesToIndex']);
        }

        if (isset($onlineSettings['attributesToIndex'])) {
            $onlineSettings['searchableAttributes'] = $onlineSettings['attributesToIndex'];
            unset($onlineSettings['attributesToIndex']);
        }

        foreach ($removes as $remove) {
            if (isset($onlineSettings[$remove])) {
                unset($onlineSettings[$remove]);
            }
        }

        foreach ($settings as $key => $value) {
            $onlineSettings[$key] = $value;
        }

        return $onlineSettings;
    }

    /**
     * Delete objects
     *
     * @param array $ids
     * @param string $indexName
     * @return void
     */
    public function deleteObjects($ids, $indexName)
    {
        $this->searchIndex->setIndexName($indexName)->deleteObjects($ids);
    }

    /**
     * Get longest attribute
     *
     * @param array $object
     * @return int|string
     */
    private function getLongestAttribute($object)
    {
        $maxLength = 0;
        $longestAttribute = '';

        foreach ($object as $attribute => $value) {
            $attributeLength = mb_strlen(json_encode($value));

            if ($attributeLength > $maxLength) {
                $longestAttribute = $attribute;

                $maxLength = $attributeLength;
            }
        }

        return $longestAttribute;
    }

    /**
     * Cast record
     *
     * @param array $object
     * @return array
     */
    private function castRecord($object)
    {
        foreach ($object as $key => &$value) {
            if (in_array($key, $this->nonCastableAttributes, true) === true) {
                continue;
            }

            $value = $this->castAttribute($value);
        }

        return $object;
    }

    /**
     * Cast attribute
     *
     * @param int|float $value
     * @return float|int
     */
    private function castAttribute($value)
    {
        if (is_numeric($value) && floatval($value) === floatval((int) $value)) {
            return (int) $value;
        }

        if (is_numeric($value) && $this->isValidFloat($value)) {
            return floatval($value);
        }

        return $value;
    }

    /**
     * This method serves to prevent parse of float values that exceed PHP_FLOAT_MAX as INF will break
     * JSON encoding.
     *
     * To further customize the handling of values that may be incorrectly interpreted as numeric by
     * PHP you can implement an "after" plugin on this method.
     *
     * @param $value - what PHP thinks is a floating point number
     * @return bool
     */
    public function isValidFloat(string $value): bool
    {
        return floatval($value) !== INF;
    }

    /**
     * Prepare records
     *
     * @param array $objects
     * @param string $indexName
     * @return void
     * @throws \Exception
     */
    private function prepareRecords(&$objects, $indexName)
    {
        $currentCET = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $currentCET = $currentCET->format('Y-m-d H:i:s');

        $modifiedIds = [];
        foreach ($objects as $key => &$object) {
            $object['meiliSearchLastUpdateAtCET'] = $currentCET;

            $previousObject = $object;

            $object = $this->handleTooBigRecord($object);

            if ($object === false) {
                $longestAttribute = $this->getLongestAttribute($previousObject);
                $modifiedIds[] = $indexName . '
                    - ID ' . $previousObject['objectID'] . ' - skipped - longest attribute: ' . $longestAttribute;

                unset($objects[$key]);

                continue;
            } elseif ($previousObject !== $object) {
                $modifiedIds[] = $indexName . ' - ID ' . $previousObject['objectID'] . ' - truncated';
            }

            $object = $this->castRecord($object);
        }

        if ($modifiedIds && $modifiedIds !== []) {
            $separator = php_sapi_name() === 'cli' ? "\n" : '<br>';

            $errorMessage = 'MeiliSearch reindexing:
                You have some records which are too big to be indexed in MeiliSearch.
                They have either been truncated
                (removed attributes: ' . implode(', ', $this->potentiallyLongAttributes) . ')
                or skipped completely: ' . $separator . implode($separator, $modifiedIds);

            if (php_sapi_name() === 'cli') {
                $this->consoleOutput->writeln($errorMessage);

                return;
            }

            $this->messageManager->addErrorMessage($errorMessage);
        }
    }

    /**
     * @param $object
     *
     * @return int
     */
    private function calculateObjectSize($object)
    {
        return mb_strlen(json_encode($object));
    }

    private function getMaxRecordSize()
    {
        if (!$this->maxRecordSize) {
            $this->maxRecordSize = $this->config->getMaxRecordSizeLimit();
        }
        return $this->maxRecordSize;
    }

    private function handleTooBigRecord($object)
    {
        $size = $this->calculateObjectSize($object);

        if ($size > $this->getMaxRecordSize()) {
            foreach ($this->potentiallyLongAttributes as $attribute) {
                if (isset($object[$attribute])) {
                    unset($object[$attribute]);

                    // Recalculate size and check if it fits in MeiliSearch index
                    $size = $this->calculateObjectSize($object);
                    if ($size < $this->getMaxRecordSize()) {
                        return $object;
                    }
                }
            }

            // If the SKU attribute is the longest, start popping off SKU's to make it fit
            // This has the downside that some products cannot be found on some of its childrens' SKU's
            // But at least the config product can be indexed
            // Always keep the original SKU though
            if ($this->getLongestAttribute($object) === 'sku' && is_array($object['sku'])) {
                foreach ($object['sku'] as $sku) {
                    if (count($object['sku']) === 1) {
                        break;
                    }

                    array_pop($object['sku']);

                    $size = $this->calculateObjectSize($object);
                    if ($size < $this->getMaxRecordSize()) {
                        return $object;
                    }
                }
            }

            // Recalculate size, if it still does not fit, let's skip it
            $size = $this->calculateObjectSize($object);
            if ($size > $this->getMaxRecordSize()) {
                $object = false;
            }
        }

        return $object;
    }

    public function castProductObject(&$productData)
    {
        foreach ($productData as $key => &$data) {
            if (in_array($key, $this->nonCastableAttributes, true) === true) {
                continue;
            }

            $data = $this->castAttribute($data);

            if (is_array($data) === false) {
                if ($data != null) {
                    $data = explode('|', $data);
                    if (count($data) === 1) {
                        $data = $data[0];
                        $data = $this->castAttribute($data);
                    } else {
                        foreach ($data as &$element) {
                            $element = $this->castAttribute($element);
                        }
                    }
                }
            }
        }
    }

    public function getObjects($indexName, $objectIds)
    {
        return $this->searchIndex->setIndexName($indexName)->getObjects($objectIds);
    }

    private function getSettings($sourceIndex)
    {
        return $this->searchIndex->setIndexName($sourceIndex)->getSettings();
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
}
