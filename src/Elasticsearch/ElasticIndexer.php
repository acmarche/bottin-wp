<?php

namespace AcMarche\Bottin\Elasticsearch;

use AcMarche\Bottin\Elasticsearch\Data\DocumentElastic;
use AcMarche\Bottin\Elasticsearch\Data\ElasticData;
use AcMarche\Common\AcSerializer;
use AcMarche\Pivot\DependencyInjection\Kernel;
use AcMarche\Theme\Inc\Theme;
use Elastica\Document;
use Elastica\Response;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;
use WP_Post;
use function switch_to_blog;

class ElasticIndexer
{
    use ElasticClientTrait;

    private SerializerInterface $serializer;
    private ElasticData $elasticData;
    private SymfonyStyle|null $outPut;

    public function __construct(?SymfonyStyle $outPut = null)
    {
        $this->connect();
        $this->serializer  = (new AcSerializer())->create();
        $this->elasticData = new ElasticData();
        $this->outPut      = $outPut;
    }

    public function indexAllPosts(array $sites = array())
    {
        if (count($sites) === 0) {
            $sites = Theme::SITES;
        }

        foreach ($sites as $siteId => $nom) {
            switch_to_blog($siteId);
            if ($this->outPut) {
                $this->outPut->section($nom);
            }
            $documentElastics = $this->elasticData->getPosts();
            foreach ($documentElastics as $documentElastic) {
                if ($this->outPut) {
                    $this->outPut->writeln($documentElastic->name);
                }
                $this->addPost($documentElastic, $siteId);
            }
        }
    }

    public function indexPost(WP_Post $post, int $siteId)
    {
        $documentElactic = $this->elasticData->postToDocumentElastic($post);
        if ($documentElactic) {
            if ($this->outPut) {
                $this->outPut->writeln($post->name);
            }
            $response = $this->addPost($documentElactic, $siteId);
            if ($response->hasError()) {
                if ($this->outPut) {
                    $this->outPut->writeln('Erreur lors de l\'indexation: '.$response->getErrorMessage());
                }
            }
        }
    }

    public function indexPagesSpecial()
    {
        $posts = $this->elasticData->indexPagesSpecial();
        foreach ($posts as $post) {
            if ($this->outPut) {
                $this->outPut->writeln($post->name);
            }
            $response = $this->addPost($post, Theme::ADMINISTRATION);
            if ($response->hasError()) {
                if ($this->outPut) {
                    $this->outPut->writeln('Erreur lors de l\'indexation: '.$response->getErrorMessage());
                }
            }
        }
    }

    public function addPost(DocumentElastic $documentElastic, int $blogId): Response
    {
        $content = $this->serializer->serialize($documentElastic, 'json');
        $id      = $this->createIdPost($documentElastic->id, $blogId);
        $doc     = new Document($id, $content);

        return $this->index->addDocument($doc);
    }

    public function deletePost(int $postId, int $siteId)
    {
        $id = $this->createIdPost($postId, $siteId);
        $this->index->deleteById($id);
    }

    protected function createIdPost(int $postId, int $siteId): string
    {
        return 'post_'.$siteId.'_'.$postId;
    }

    public function indexAllCategories(array $sites = array())
    {
        if (count($sites) === 0) {
            $sites = Theme::SITES;
        }

        foreach ($sites as $siteId => $nom) {
            switch_to_blog($siteId);
            if ($this->outPut) {
                $this->outPut->section($nom);
            }
            $categories = $this->elasticData->getCategoriesBySite();
            foreach ($categories as $documentElastic) {
                $this->addCategory($documentElastic, $siteId);
                if ($this->outPut) {
                    $this->outPut->writeln($documentElastic->name);
                }
            }
        }
    }

    private function addCategory(DocumentElastic $documentElastic, int $blodId)
    {
        $content  = $this->serializer->serialize($documentElastic, 'json');
        $id       = 'category_'.$blodId.'_'.$documentElastic->id;
        $doc      = new Document($id, $content);
        $response = $this->index->addDocument($doc);
        if ($response->hasError()) {
            if ($this->outPut) {
                $this->outPut->writeln('Erreur lors de l\'indexation: '.$response->getErrorMessage());
            }
        }
    }

    public function indexAllBottin()
    {
        $this->indexFiches();
        $this->indexCategoriesBottin();
    }

    public function indexCategoriesBottin()
    {
        $categories = $this->elasticData->getAllCategoriesBottin($this->outPut);
        foreach ($categories as $documentElastic) {
            $content  = $this->serializer->serialize($documentElastic, 'json');
            $id       = 'bottin_cat_'.$documentElastic->id;
            $doc      = new Document($id, $content);
            $response = $this->index->addDocument($doc);
            if ($this->outPut) {
                $this->outPut->writeln("Bottin cat: ".$documentElastic->name.' '.$documentElastic->count.'fiches');
                if ($response->hasError()) {
                    $this->outPut->writeln('Erreur lors de l\'indexation: '.$response->getErrorMessage());
                }
            }
        }
    }

    public function indexFiches()
    {
        $fiches = $this->elasticData->getAllfiches();
        foreach ($fiches as $documentElastic) {
            $content = $this->serializer->serialize($documentElastic, 'json');
            $id      = 'fiche_'.$documentElastic->id;
            $doc     = new Document($id, $content);
            $this->index->addDocument($doc);
            if ($this->outPut) {
                $this->outPut->writeln($documentElastic->name);
            }
        }
    }

    public function indexEnquetes()
    {
        switch_to_blog(Theme::ADMINISTRATION);
        foreach ($this->elasticData->getEnquetesDocumentElastic() as $documentElastic) {
            $content = $this->serializer->serialize($documentElastic, 'json');
            $id      = 'enquete_'.$documentElastic->id;
            $doc     = new Document($id, $content);
            $this->index->addDocument($doc);
            if ($this->outPut) {
                $this->outPut->writeln($documentElastic->name);
            }
        }
    }
}
