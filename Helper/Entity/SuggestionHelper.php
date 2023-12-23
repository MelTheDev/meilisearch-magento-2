<?php

namespace MelTheDev\MeiliSearch\Helper\Entity;

use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Search\Model\Query;
use Magento\Search\Model\ResourceModel\Query\Collection as QueryCollection;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory as QueryCollectionFactory;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;

class SuggestionHelper
{
    /**
     * @var string
     */
    public const POPULAR_QUERIES_CACHE_TAG = 'melthedev_meilisearch_popular_queries_cache_tag';

    /**
     * @param QueryCollectionFactory $queryCollectionFactory
     * @param ConfigCache $cache
     * @param ConfigHelper $configHelper
     * @param SerializerInterface $serializer
     * @noinspection PhpPropertyCanBeReadonlyInspection
     */
    public function __construct(
        private QueryCollectionFactory $queryCollectionFactory,
        private ConfigCache $cache,
        private ConfigHelper $configHelper,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @return string
     */
    public function getIndexNameSuffix()
    {
        return '_suggestions';
    }

    /**
     * @return array
     */
    public function getIndexSettings()
    {
        $indexSettings = [
            'searchableAttributes' => ['query'],
            'customRanking'        => ['desc(popularity)', 'desc(number_of_results)', 'asc(date)'],
            'typoTolerance'        => false,
            'attributesToRetrieve' => ['query'],
        ];

        $transport = new DataObject($indexSettings);
        return $transport->getData();
    }

    /**
     * @param Query $suggestion
     * @return array|mixed|null
     */
    public function getObject(Query $suggestion)
    {
        $suggestionObject = [
            'objectID'          => $suggestion->getData('query_id'),
            'query'             => $suggestion->getData('query_text'),
            'number_of_results' => (int) $suggestion->getData('num_results'),
            'popularity'        => (int) $suggestion->getData('popularity'),
            'updated_at'        => (int) strtotime($suggestion->getData('updated_at')),
        ];

        $transport = new DataObject($suggestionObject);
        return $transport->getData();
    }

    /**
     * @param $storeId
     * @return array|bool|float|int|string|null
     */
    public function getPopularQueries($storeId = null)
    {
        if (!$this->configHelper->isInstantEnabled($storeId) || !$this->configHelper->showSuggestionsOnNoResultsPage($storeId)) {
            return [];
        }
        $queries = $this->cache->load(self::POPULAR_QUERIES_CACHE_TAG . '_' . $storeId);
        if ($queries !== false) {
            return $this->serializer->unserialize($queries);
        }

        /** @var QueryCollection $collection */
        $collection = $this->queryCollectionFactory->create();
        $collection->getSelect()->where(
            'num_results >= ' . $this->configHelper->getMinNumberOfResults() . '
            AND popularity >= ' . $this->configHelper->getMinPopularity() . '
            AND query_text != "__empty__" AND CHAR_LENGTH(query_text) >= 3'
        );

        if ($storeId) {
            $collection->getSelect()->where('store_id = ?', (int) $storeId);
        }

        $collection->setOrder('popularity', 'DESC');
        $collection->setOrder('num_results', 'DESC');
        $collection->setOrder('updated_at', 'ASC');

        $collection->getSelect()->limit(10);

        $queries = $collection->getColumnValues('query_text');

        $this->cache->save(
            $this->serializer->serialize($queries),
            self::POPULAR_QUERIES_CACHE_TAG . '_' . $storeId,
            [],
            $this->configHelper->getCacheTime($storeId)
        );

        return $queries;
    }

    /**
     * @param $storeId
     * @return QueryCollection
     */
    public function getSuggestionCollectionQuery($storeId)
    {
        /** @var QueryCollection $collection */
        $collection = $this->queryCollectionFactory->create()
            ->addStoreFilter($storeId)
            ->setStoreId($storeId);

        $collection->getSelect()->where(
            'num_results >= ' . $this->configHelper->getMinNumberOfResults($storeId) . '
            AND popularity >= ' . $this->configHelper->getMinPopularity($storeId) . '
            AND query_text != "__empty__"'
        );
        return $collection;
    }
}
