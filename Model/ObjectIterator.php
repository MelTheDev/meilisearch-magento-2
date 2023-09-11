<?php

namespace MelTheDev\MeiliSearch\Model;

use Meilisearch\Contracts\DocumentsQuery;

class ObjectIterator extends Iterators
{
    /**
     * @inheritDoc
     */
    protected function fetchNextBatch()
    {
        $document = (new DocumentsQuery())
            ->setOffset($this->offset)
            ->setLimit($this->limit)
            ->setFields($this->fields);
        $this->currentResults = $this->clientIndex->getDocuments($document)->getResults();

        $this->offset += $this->limit;
        $this->currentPosition = 0;
    }
}
