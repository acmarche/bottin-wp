<?php

namespace AcMarche\Bottin\Elasticsearch\Adl;

use AcMarche\Bottin\Elasticsearch\Http\ConnectionTrait;

class AdlClient
{
    use ConnectionTrait;

    public function __construct(private string $baseUrl)
    {
    }

    public function getAllCategories(): array
    {
        $this->connect($this->baseUrl);
        $dataString = $this->executeRequest($this->baseUrl.'/categories');

        return json_decode($dataString);
    }

    public function getAllPosts(): array
    {
        $this->connect($this->baseUrl);
        $dataString = $this->executeRequest($this->baseUrl.'/posts');

        return json_decode($dataString);
    }

    public function getPostsByIdCategory(int $categoryId): array
    {
        $this->connect($this->baseUrl);
        $dataString = $this->executeRequest($this->baseUrl.'/posts/?categories='.$categoryId);

        return json_decode($dataString);
    }
}