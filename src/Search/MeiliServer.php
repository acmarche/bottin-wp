<?php

namespace AcMarche\Bottin\Search;

use AcMarche\Bottin\SearchData\Adl\AdlIndexer;
use AcMarche\Bottin\SearchData\Data\ElasticData;
use AcMarche\Theme\Inc\Theme;
use Meilisearch\Contracts\DeleteTasksQuery;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Endpoints\Keys;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MeiliServer
{
    use MeiliTrait;

    private ElasticData $elasticData;
    private array $skips = [679, 705, 707];
    private string $primaryKey = 'id';
    private ?Indexes $index = null;

    public function __construct(
        #[Autowire(env: 'MEILI_INDEX_NAME')]
        private string $indexName,
        #[Autowire(env: 'MEILI_MASTER_KEY')]
        private string $masterKey,
    ) {
        $this->elasticData = new ElasticData();

    }

    /**
     *
     * @return array<'taskUid','indexUid','status','enqueuedAt'>
     */
    public function createIndex(): array
    {
        $this->init();
        $this->client->deleteTasks((new DeleteTasksQuery())->setStatuses(['failed', 'canceled', 'succeeded']));
        $this->client->deleteIndex($this->indexName);

        return $this->client->createIndex($this->indexName, ['primaryKey' => $this->primaryKey]);
    }

    /**
     * https://raw.githubusercontent.com/meilisearch/meilisearch/latest/config.toml
     * @return array
     */
    public function settings(): array
    {
        //don't return same fiches. Suppose you have numerous black jackets in different sizes in your costumes index
        //$this->client->index($this->indexName)->updateDistinctAttribute('societe');

        /*$this->client->index($this->indexName)->updateSearchableAttributes([
            'title',
            'overview',
            'genres',
        ]);*/

        return $this->client->index($this->indexName)->updateFilterableAttributes($this->facetFields);
    }

    public function addContent()
    {
        $this->init();

        $this->indexAllPosts();
        $this->indexAllCategories();
        $this->indexAllBottin();
        $this->indexEnquetes();
        $this->indexAdl();
    }

    public function indexAllPosts(array $sites = array())
    {
        if (count($sites) === 0) {
            $sites = Theme::SITES;
        }
        $documents = [];
        foreach ($sites as $siteId => $nom) {
            switch_to_blog($siteId);
            $documentElastics = $this->elasticData->getPosts(null, $siteId);
            foreach ($documentElastics as $documentElastic) {
                $documents[] = $documentElastic;
            }
        }

        $this->index->addDocuments($documents, $this->primaryKey);
    }

    public function indexPost(\WP_Post $post, int $siteId)
    {
        $this->init();
        $documentElactic = $this->elasticData->postToDocumentElastic($post, $siteId);
        if ($documentElactic) {
            $this->index->addDocuments([$documentElactic], $this->primaryKey);
        }
    }

    public function deletePost(int $postId, int $siteId)
    {
        $this->init();
        $elasticData = new ElasticData();
        $id = $elasticData->createId($postId, "post", $siteId);
        $this->index->deleteDocument($id);
    }

    public function indexAllCategories(array $sites = array())
    {
        if (count($sites) === 0) {
            $sites = Theme::SITES;
        }
        $documents = [];
        foreach ($sites as $siteId => $nom) {
            switch_to_blog($siteId);
            $categories = $this->elasticData->getCategoriesBySite($siteId);
            foreach ($categories as $documentElastic) {
                $documentElastic->id = 'category-'.$documentElastic->id.'-'.$siteId;
                $documents[] = $documentElastic;
            }
        }
        $this->index->addDocuments($documents, $this->primaryKey);
    }

    public function indexAllBottin()
    {
        $this->indexFiches();
        $this->indexCategoriesBottin();
    }

    public function indexCategoriesBottin()
    {
        $documents = [];
        $categories = $this->elasticData->getAllCategoriesBottin();
        foreach ($categories as $documentElastic) {
            if (in_array($documentElastic->id, $this->skips)) {
                continue;
            }
            $id = 'bottin_cat_'.$documentElastic->id;
            $documentElastic->id = $id;
            $documents[] = $documentElastic;
        }
        $this->index->addDocuments($documents, $this->primaryKey);
    }

    public function indexFiches()
    {
        $documents = [];
        $fiches = $this->elasticData->getAllfiches();
        foreach ($fiches as $documentElastic) {
            $skip = false;
            foreach ($documentElastic->ids as $categoryId) {
                if (in_array($categoryId, $this->skips)) {
                    $skip = true;
                }
            }
            if ($skip) {
                continue;
            }
            $id = 'fiche_'.$documentElastic->id;
            $documentElastic->id = $id;
        }
        $this->index->addDocuments($documents, $this->primaryKey);
    }

    public function indexEnquetes()
    {
        $documents = [];
        switch_to_blog(Theme::ADMINISTRATION);
        foreach ($this->elasticData->getEnquetesDocumentElastic() as $documentElastic) {
            $id = 'enquete_'.$documentElastic->id;
            $documentElastic->id = $id;
            $documents[] = $documentElastic;
        }
        $this->index->addDocuments($documents, $this->primaryKey);
    }

    public function indexAdl()
    {
        $documents = [];
        $adlIndexer = new AdlIndexer();
        foreach ($adlIndexer->getAllCategories() as $documentElastic) {
            $id = 'adl_cat_'.$documentElastic->id;
            $documentElastic->id = $id;
            $documents[] = $documentElastic;
        }

        foreach ($adlIndexer->getAllPosts() as $documentElastic) {
            $id = 'adl_'.$documentElastic->id;
            $documentElastic->id = $id;
            $documents[] = $documentElastic;
        }
        $this->index->addDocuments($documents, $this->primaryKey);
    }

    public function createKey(): Keys
    {
        $this->init();

        return $this->client->createKey([
            'description' => 'Bottin API key',
            'actions' => ['*'],
            'indexes' => [$this->indexName],
            'expiresAt' => '2042-04-02T00:42:42Z',
        ]);
    }
}