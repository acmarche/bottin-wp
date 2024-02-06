<?php

namespace AcMarche\Bottin\Search;

use Meilisearch\Client;

trait MeiliTrait
{
    public ?Client $client = null;
    private array $facetFields = ['_geo', 'type'];

    public function init(): void
    {
        if (!$this->client) {
            $this->client = new Client('http://127.0.0.1:7700', $this->masterKey);

        }

        if (!$this->index) {
            $this->index = $this->client->index($this->indexName);
        }
    }
}