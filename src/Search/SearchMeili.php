<?php

namespace AcMarche\Bottin\Search;

use Meilisearch\Search\SearchResult;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SearchMeili
{
    use MeiliTrait;

    public function __construct(
        #[Autowire(env: 'MEILI_INDEX_NAME')]
        private string $indexName,
        #[Autowire(env: 'MEILI_MASTER_KEY')]
        private string $masterKey,
    ) {
    }

    /**
     * https://www.meilisearch.com/docs/learn/fine_tuning_results/filtering
     * @param string $keyword
     * @param string|null $localite
     * @return iterable|SearchResult
     */
    public function doSearch(string $keyword, string $localite = null): iterable|SearchResult
    {
        $this->init();
        $filters = ['filter' => ['type = fiche']];
        if ($localite) {
            $filters['filter'] = ['localite = '.$localite];
        }

        return $this->index->search($keyword, []);
    }

}