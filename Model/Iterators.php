<?php

namespace MelTheDev\MeiliSearch\Model;

use Meilisearch\Client;
use MelTheDev\MeiliSearch\Model\Client\SearchIndex;

abstract class Iterators implements \Iterator
{
    protected $clientIndex;
    /**
     * @var int
     */
    protected $offset = 0;
    /**
     * @var array
     */
    protected $currentResults = [];
    /**
     * @var int
     */
    protected $currentPosition = 0;

    /**
     * @param Client $client
     * @param string $indexName
     * @param array $fields
     * @param int $limit
     */
    public function __construct(
        Client $client,
        string $indexName,
        protected array $fields = [],
        protected int $limit = SearchIndex::DEFAULT_PAGE_LIMIT
    ) {
        $this->clientIndex = $client->getIndex($indexName);
        $this->fetchNextBatch();
    }

    /**
     * Call MeliSearch' API to get new result batch.
     */
    abstract protected function fetchNextBatch();

    /**
     * Rewind the Iterator to the first element.
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->offset = 0;
        $this->fetchNextBatch();
    }

    /**
     * Checks if current position is valid. If the current position
     * is not valid, we call MeiliSearch' API to load more results
     * until it's the last page.
     *
     * @return bool the return value will be casted to boolean and then evaluated.
     *              Returns true on success or false on failure
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        if ($this->currentPosition >= $this->limit) {
            $this->fetchNextBatch();
        }
        return isset($this->currentResults[$this->currentPosition]);
    }

    /**
     * Return the current element.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->currentResults[$this->currentPosition];
    }

    /**
     * Return the key of the current element.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->offset - $this->limit + $this->currentPosition;
    }

    /**
     * Move forward to next element.
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->currentPosition++;
    }
}
