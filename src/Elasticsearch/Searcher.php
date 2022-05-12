<?php


namespace AcMarche\Bottin\Elasticsearch;

use Elastica\Exception\InvalidException;
use Elastica\Query\BoolQuery;
use Elastica\Query\MatchQuery;
use Elastica\Query\MultiMatch;
use Elastica\Query\SimpleQueryString;
use Elastica\Suggest as SuggestElastica;
use Elastica\ResultSet;
use Elastica\Suggest\Term as TermElastica;

/**
 * https://github.com/ruflin/Elastica/tree/master/tests
 * Class Searcher
 * @package AcMarche\Elasticsearch
 */
class Searcher
{
    use ElasticClientTrait;

    public function __construct()
    {
        $this->connect();
    }

    /**
     * @param string $keywords
     *
     * @return ResultSet
     * @throws  InvalidException
     */
    public function search2(string $keywords): ResultSet
    {
        $query               = new BoolQuery();
        $matchName           = new MatchQuery('name', $keywords);
        $matchNameStemmed    = new MatchQuery('name.stemmed', $keywords);
        $matchContent        = new MatchQuery('content', $keywords);
        $matchContentStemmed = new MatchQuery('content.stemmed', $keywords);
        $matchExcerpt        = new MatchQuery('excerpt', $keywords);
        $matchCatName        = new MatchQuery('tags', $keywords);
        $query->addShould($matchName);
        $query->addShould($matchNameStemmed);
        $query->addShould($matchExcerpt);
        $query->addShould($matchContent);
        $query->addShould($matchContentStemmed);
        $query->addShould($matchCatName);

        $result = $this->index->search($query);

        return $result;
    }

    /**
     * @param string $keywords
     * @param int $limit
     *
     * @return ResultSet
     */
    public function search(string $keywords, int $limit = 50): ResultSet
    {
        $options = ['limit' => $limit];
        $query   = new MultiMatch();
        $query->setFields(
            [
                'name^1.2',
                'name.stemmed',
                'content',
                'content.stemmed',
                'excerpt',
                'tags',
            ]
        );
        $query->setQuery($keywords);
        $query->setType(MultiMatch::TYPE_MOST_FIELDS);

        $result = $this->index->search($query, $options);

        return $result;
    }

    /**
     * @param string $keyword
     *
     * @return array|callable|ResultSet
     * {
     * "suggest": {
     * "movie-suggest-fuzzy": {
     * "prefix": "conseil",
     * "completion": {
     * "field": "name2.completion",
     * "fuzzy": {
     * "fuzziness": 1
     * }
     * }
     * }
     * }
     * }
     */
    public function suggest(string $keyword)
    {
        $suggest = new SuggestElastica();

        $suggest1 = new SuggestElastica\Completion('suggest1', 'name.completion');
        $suggest->addSuggestion($suggest1->setPrefix($keyword));

        $suggest2 = new TermElastica('suggest2', 'name.completion');
        $suggest->addSuggestion($suggest2->setText($keyword));

        $suggest3 = new SuggestElastica\Phrase('suggest3', 'name.edgengram');
        $suggest->addSuggestion($suggest3->setPrefix($keyword));

     //   $suggest4 = new SuggestElastica\Completion('suggest4', 'name.autocomplete');bug
      //  $suggest->addSuggestion($suggest4->setPrefix($keyword));

        $results = $this->index->search($suggest);

        return $results;
    }

    /**
     * https://camillehdl.dev/php-compose-elastica-queries/
     */
    public function search3()
    {
        $request       = [
            "title" => ["q" => "Apocalypse"],
            "genre" => ["q" => ["war", "horror"], "operator" => "OR"],
        ];
        $query         = new BoolQuery();
        $fullTextQuery = new SimpleQueryString(
            $request["title"]["q"]."*",
            [
                "title",
            ]
        );
        $fullTextQuery->setDefaultOperator(SimpleQueryString::OPERATOR_AND);
        $fullTextQuery->setParam("analyze_wildcard", true);
        $query->addMust($fullTextQuery);
    }
}
