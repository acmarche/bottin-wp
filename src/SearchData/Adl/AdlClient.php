<?php

namespace AcMarche\Bottin\SearchData\Adl;

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
        $dataString = $this->executeRequest($this->baseUrl.'/posts/?per_page=100');

        return json_decode($dataString);
    }

    public function getPostsByIdCategory(int $categoryId): array
    {
        $this->connect($this->baseUrl);
        $dataString = $this->executeRequest($this->baseUrl.'/posts/?categories='.$categoryId);

        return json_decode($dataString);
    }
}