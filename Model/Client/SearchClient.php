<?php

namespace MelTheDev\MeiliSearch\Model\Client;

use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use Meilisearch\ClientFactory as MeiliSearchClient;

class SearchClient
{
    /** @var ConfigHelper */
    private ConfigHelper $configHelper;
    /** @var MeiliSearchClient */
    private MeiliSearchClient $clientFactory;

    /**
     * SearchClient constructor.
     * @param ConfigHelper $configHelper
     * @param MeiliSearchClient $clientFactory
     */
    public function __construct(
        ConfigHelper      $configHelper,
        MeiliSearchClient $clientFactory
    ) {
        $this->configHelper  = $configHelper;
        $this->clientFactory = $clientFactory;
    }

    /**
     * @return \Meilisearch\Client
     */
    public function createClient()
    {
        return $this->clientFactory->create([
            'url'    => $this->configHelper->getApiUrl(),
            'apiKey' => $this->configHelper->getApiKey()
        ]);
    }
}
