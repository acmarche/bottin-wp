<?php

namespace AcMarche\Bottin\SearchData\Adl;

use AcMarche\Bottin\SearchData\Data\Cleaner;
use AcMarche\Bottin\SearchData\Data\DocumentElastic;
use AcMarche\Common\Env;

class AdlIndexer
{
    private AdlClient|null $adlClient = null;

    public function getAllCategories(): array
    {
        if (!$this->adlClient) {
            $this->adlClient = new AdlClient($_ENV['ADL_URL']);
        }

        foreach ($this->adlClient->getAllCategories() as $category) {

            $description = '';
            if ($category->description) {
                $description = Cleaner::cleandata($category->description);
            }

            $today = new \DateTime();
            $date = $today->format('Y-m-d');
            $content = $description;

            foreach ($this->getPostsByCategoryId($category->id) as $documentElastic) {
                $content .= $documentElastic->name;
                $content .= $documentElastic->excerpt;
                $content .= $documentElastic->content;
            }

            $document = new DocumentElastic();
            $document->id = $category->id;
            $document->name = Cleaner::cleandata($category->name);
            $document->excerpt = $description;
            $document->content = $content;
            $document->tags = [];
            $document->date = $date;
            $document->url = $category->link;

            $datas[] = $document;
        }

        return $datas;
    }

    /**
     * @return DocumentElastic[]
     */
    public function getAllPosts(): array
    {
        if (!$this->adlClient) {
            $this->adlClient = new AdlClient($_ENV['ADL_URL']);
        }

        $data = [];
        foreach ($this->adlClient->getAllPosts() as $post) {
            $data[] = $this->createDocumentElastic($post);
        }

        return $data;
    }

    /**
     * @return DocumentElastic[]
     */
    private function getPostsByCategoryId(int $cat_ID): array
    {
        $posts = $this->adlClient->getPostsByIdCategory($cat_ID);
        $datas = [];

        foreach ($posts as $post) {
            $document = $this->createDocumentElastic($post);
            $datas[] = $document;
        }

        return $datas;
    }

    private function createDocumentElastic(object $post): DocumentElastic
    {
        $categories = [];

        $content = $post->content->rendered;

        $document = new DocumentElastic();
        $document->id = $post->id;
        $document->name = Cleaner::cleandata($post->title->rendered);
        $document->excerpt = Cleaner::cleandata($post->excerpt->rendered);
        $document->content = Cleaner::cleandata($content);
        $document->tags = $categories;
        $document->date = $post->modified;
        $document->url = $post->link;

        return $document;
    }

}