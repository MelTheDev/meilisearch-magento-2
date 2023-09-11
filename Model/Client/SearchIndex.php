<?php

namespace MelTheDev\MeiliSearch\Model\Client;

use MelTheDev\MeiliSearch\Exception\MissingObjectId;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Helper\Helpers;
use MelTheDev\MeiliSearch\Model\ObjectIterator;

class SearchIndex
{
    /** @var string */
    public const PRIMARY_KEY = 'objectID';
    /** @var int */
    public const DEFAULT_PAGE_LIMIT = 20;

    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;
    /**
     * @var string
     */
    private $indexName;
    /**
     * @var SearchClient
     */
    private SearchClient $searchClient;

    /**
     * SearchIndex constructor.
     * @param ConfigHelper $configHelper
     * @param SearchClient $searchClient
     */
    public function __construct(
        ConfigHelper $configHelper,
        SearchClient $searchClient
    ) {
        $this->configHelper = $configHelper;
        $this->searchClient = $searchClient;
    }

    /**
     * Get index name
     *
     * @return string
     */
    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * @param string $indexName
     * @return $this
     */
    public function setIndexName(string $indexName)
    {
        $this->indexName = $indexName;
        return $this;
    }

    public function getObjects($objectIds, $requestOptions = [])
    {
        /*$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $client = $objectManager->create(\Meilisearch\ClientFactory::class)->create([
            'url' => 'http://127.0.0.1:7700',
            'apiKey' => 'MASTER_KEY'
        ])->index('magento_products');
        $data = [];
        foreach (array_chunk($objectIds, 100) as $chunk) {
            foreach ($chunk as $objectId) {
                $request = $client->getDocument($objectId, ['objectID']);
                die;
            }
        }
        die;*/
        return [];
    }

    /**
     * @param array $objects
     * @param array $requestOptions
     * @return string
     * @throws MissingObjectId
     */
    public function saveObjects($objects, $requestOptions = [])
    {
        if (isset($requestOptions['autoGenerateObjectIDIfNotExist'])
            && $requestOptions['autoGenerateObjectIDIfNotExist']) {
            unset($requestOptions['autoGenerateObjectIDIfNotExist']);

            return $this->addObjects($objects, $requestOptions);
        }
        return '';
    }

    public function deleteObjects($objectIds, $requestOptions = [])
    {
        /*$objects = array_map(function ($id) {
            return ['objectID' => $id];
        }, $objectIds);*/
        //$objects = $objectIds;

        return $this->splitIntoBatches('deleteObject', $objectIds, $requestOptions);
    }

    /**
     * @param array $objects
     * @param array $requestOptions
     * @return string
     * @throws MissingObjectId
     */
    protected function addObjects($objects, $requestOptions = [])
    {
        return $this->splitIntoBatches('addObject', $objects, $requestOptions);
    }

    /**
     * @param $action
     * @param $objects
     * @param array $requestOptions
     * @return string
     * @throws MissingObjectId
     */
    protected function splitIntoBatches($action, $objects, $requestOptions = [])
    {
        $allResponses = [];
        $batch        = [];
        $batchSize    = $this->configHelper->getBatchSize();
        $count = 0;

        foreach ($objects as $object) {
            $batch[] = $object;
            $count++;

            if ($count === $batchSize) {
                if ('addObject' !== $action && $action != 'deleteObject') {
                    Helpers::ensureObjectID($batch, 'All objects must have an unique objectID (like a primary key) to be valid.');
                }
                //$allResponses[] = $this->rawBatch(Helpers::buildBatch($batch, $action), $requestOptions);
                $allResponses[] = $this->rawBatch($batch, $requestOptions, $action);
                $batch = [];
                $count = 0;
            }
        }

        if ('addObject' !== $action && $action != 'deleteObject') {
            Helpers::ensureObjectID($batch, 'All objects must have an unique objectID (like a primary key) to be valid.');
        }

        // If not calls were made previously, not objects are passed
        // so we return a NullResponse
        // If there are already responses and something left in the
        // batch, we send it.
        if (empty($allResponses) && empty($batch)) {
            return '';
        } elseif (!empty($batch)) {
            //$allResponses[] = $this->rawBatch(Helpers::buildBatch($batch, $action), $requestOptions);
            $allResponses[] = $this->rawBatch($batch, $requestOptions, $action);
        }

        return '';
    }

    /**
     * @param array $requests
     * @param array $requestOptions
     * @param string|null $action
     * @return array
     * @throws \Exception
     */
    protected function rawBatch($requests, $requestOptions = [], $action = null)
    {
        $client = $this->searchClient->createClient();
        if ($action == 'addObject') {
            $result = $client->index($this->getIndexName())->addDocuments($requests, self::PRIMARY_KEY);
            return $requests;
        } elseif ($action == 'deleteObject') {
            $result = $client->index($this->getIndexName())->deleteDocuments($requests);
            return $requests;
        }
        return [];
    }

    public function setSettings($settings)
    {
        $client = $this->searchClient->createClient();
        $client->index($this->getIndexName())->updateSettings($settings);
    }

    /**
     * Get MeiliSearch settings
     *
     * @return array
     */
    public function getSettings()
    {
        $client = $this->searchClient->createClient();
        return $client->index($this->getIndexName())->getSettings();
    }

    /**
     * @return ObjectIterator
     */
    public function browseObjects($fieldsToSelect = [], $limit = self::DEFAULT_PAGE_LIMIT)
    {
        return new ObjectIterator($this->searchClient->createClient(), $this->getIndexName(), $fieldsToSelect, $limit);
    }

    /**
     * MeiliSearch Health Status
     *
     * @return bool
     */
    public function isHealthy()
    {
        return $this->searchClient->createClient()->isHealthy();
    }
}