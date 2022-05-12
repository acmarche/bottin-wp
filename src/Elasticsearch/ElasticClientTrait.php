<?php

namespace AcMarche\Bottin\Elasticsearch;

use AcMarche\Common\Env;
use Elastica\Client;
use Elastica\Index;

trait ElasticClientTrait
{
    public Client $client;
    private  Index$index;

    public function connect(string $host = 'localhost', int $port = 9200)
    {
        Env::loadEnv();
        $username = $_ENV['ELASTIC_USER'];
        $password = $_ENV['ELASTIC_PASSWORD'];
        $ds= $username.':'.$password.'@'.$host;
        $this->client = new Client(
            [
                'host' => $ds,
                'port' => $port,
            ]
        );
        //$this->client->setLogger(); todo
        $this->setIndex(ElasticServer::INDEX_NAME_MARCHE_BE);
    }

    public function setIndex(string $name)
    {
        $this->index = $this->client->getIndex($name);
    }
}
