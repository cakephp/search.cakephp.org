<?php
declare(strict_types=1);

namespace App\Model\Index;

use App\Datasource\Query as AppQuery;
use App\QueryTranslation\QueryString;
use Cake\ElasticSearch\Index;
use Elastica\Aggregation\Cardinality;
use Elastica\Query\BoolQuery;
use Elastica\Query\FunctionScore;
use Elastica\Query\QueryString as QueryStringQuery;
use Elastica\Result;
use Elastica\Script\Script;

class SearchIndex extends Index
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    /**
     * Search the index
     *
     * @param string $lang The language to search in.
     * @param string $version The version to search in.
     * @param \App\QueryTranslation\QueryString $queryString The search query string.
     * @param mixed[] $options The search options.
     * @return mixed[]
     */
    public function search(string $lang, string $version, QueryString $queryString, array $options = []): array
    {
        $options += [
            'query' => '',
            'page' => 1,
            'limit' => 10,
            'sort' => [
                '_score' => ['order' => 'desc'],
                // On score ties, prefer results with a higher page position, assuming
                // those results have a higher chance of being more relevant.
                'position' => ['order' => 'asc'],
            ],
            'highlightPreTag' => '',
            'highlightPostTag' => '',
            'encoder' => 'default',
        ];

        $this->setName(implode('-', ['cake-docs', $version, $lang]));

        $minLimit = 1;
        $maxLimit = 100;
        $limit = max($minLimit, min($maxLimit, $options['limit']));

        $minPage = 1;
        $maxPage = 10000 / $limit; // default index.max_result_window / limit
        $page = max($minPage, min($maxPage, $options['page']));

        $resultSet = $this
            ->buildSearchQuery($queryString, compact('limit', 'page') + $options)
            ->all();

        $results = $this->formatResults($resultSet->getResults(), $options);

        return [
            'page' => $page,
            'total' => $resultSet->getAggregation('collapse_count')['value'],
            'data' => $results,
            'terms' => $queryString->extractMatchableTerms(),
        ];
    }

    /**
     * Creates a new Query instance for this repository
     *
     * @return \App\Datasource\Query
     */
    public function query()
    {
        return new AppQuery($this);
    }

    /**
     * Builds the search query.
     *
     * @param \App\QueryTranslation\QueryString $queryString The search query string.
     * @param mixed[] $options The search options.
     * @return \App\Datasource\Query
     */
    protected function buildSearchQuery(QueryString $queryString, array $options): AppQuery
    {
        return $this
            ->query()
            ->limit($options['limit'])
            ->page($options['page'])
            ->order($options['sort'])
            ->highlight([
                'pre_tags' => [$options['highlightPreTag']],
                'post_tags' => [$options['highlightPostTag']],
                'encoder' => $options['encoder'],
                'fields' => [
                    'contents' => [
                        'fragment_size' => 60,
                        'number_of_fragments' => 3,
                    ],
                    'hierarchy' => [
                        'number_of_fragments' => 0,
                    ],
                ],
            ])

            // Collapse results based on the docs page URL, this will return
            // the best match per page, instead of possibly lots of results
            // from a single page, which could hide relevant results of
            // other pages.
            ->collapse('page_url')

            // Cardinality is used to figure the number of matches _after_ collapsing,
            // as the total returned for the query is the number of matches _before_
            // collapsing. While this is an approximation, it's good enough given the
            // rather small number of documents, and the fact there wouldn't be any
            // good results on page 10+ anyways.
            ->aggregate(
                (new Cardinality('collapse_count'))
                    ->setField('page_url')
                    ->setPrecisionThreshold(600)
            )

            ->setFullQuery(
                (new FunctionScore())
                    // Apply linear decay based on the section's nesting level. Deeper nested
                    // sections tend to repeat keywords, or words that are similar, which in
                    // many cases degrades the results. For example a title may contain `template`
                    // multiple times, like in `Customizing the Templates FormHelper Uses >
                    // Adding Additional Template Variables to Templates`, reducing the chance for
                    // a root section named `View Templates` to achieve a higher score.
                    //
                    // Similar can be achieved by cranking up the boosting of more exact matches
                    // in the title field, but that has other unwanted side effects.
                    //
                    // We don't use built-in decay functions here, as they cannot make use of the
                    // page scoped max nesting level.
                    ->addScriptScoreFunction(
                        new Script("
                            double range = 0.2;
                            double distance = (double)doc['level'].value;
                            double scale = (double)Math.max(1, doc['max_level'].value);

                            return 1 - ((distance / scale) * range);
                        ")
                    )

                    // Apply linear decay on the section's position in the docs page. Lower
                    // positioned sections tend to be less relevant, respectively tend to
                    // repeat keywords that can degrade the results similar to deeper
                    // nested described above.
                    //
                    // Ideally this should affect the score on a page scoped basis, however
                    // that's not really possible, so we go with a smaller decay as a
                    // compromise between affecting per-page and overall results.
                    ->addScriptScoreFunction(
                        new Script("
                            double range = 0.1;
                            double distance = (double)doc['position'].value;
                            double scale = (double)Math.max(1, doc['max_position'].value);

                            return 1 - ((distance / scale) * range);
                        ")
                    )

                    // Content marked as being low priority is being deboosted, this allows to
                    // treat stuff like appendices as less relevant, nobody wants x migration
                    // guide results at the top when searching for eg `routing`, overshadowing
                    // stuff like `routing prefix`.
                    //
                    // This can be achieved using a boosting query too, but in ES < 7.something,
                    // highlighting of prefix matches (and non-plain highlighting) breaks when
                    // using boosting queries :(
                    ->addScriptScoreFunction(new Script(
                        "return doc['priority'].value == 'low' ? 0.1 : 1.0"
                    ))

                    ->setScoreMode(FunctionScore::SCORE_MODE_MULTIPLY)
                    ->setBoostMode(FunctionScore::BOOST_MODE_MULTIPLY)

                    ->setQuery(
                        (new BoolQuery())
                            // There must be a match in either the hierarchy, or the contents,
                            // with the hierarchy being boosted, so that ideally results from
                            // the content should only significantly affect the relevancy in case
                            // the match in the hierarchy is rather bad, or even non-existent.
                            ->addMust(
                                (new BoolQuery())
                                    // Somewhat exact prefix matching that finds incomplete words should be
                                    // most relevant.
                                    ->addShould(
                                        (new QueryStringQuery($queryString->toPrefix()))
                                            ->setPhraseSlop(2)
                                            ->setFields(['hierarchy^10', 'contents^2'])
                                            ->setDefaultOperator('AND')
                                    )

                                    // Fuzzy matching that accounts for typos should be less relevant,
                                    // and it should only be done on the hierarchy, as content, even when
                                    // not boosted, can degrade the results quite quickly as it can easily
                                    // produce lots and lots of matches.
                                    ->addShould(
                                        (new QueryStringQuery($queryString->toFuzzy()))
                                            ->setPhraseSlop(2)
                                            ->setFields(['hierarchy'])
                                            ->setDefaultOperator('AND')
                                    )
                            )

                            // Optional, more exact title matches should make the result more relevant.
                            // This counters some of the imbalances introduced by fuzzy matching on
                            // the hierarchy, which cannot adequately be fixed by reducing its boosting.
                            //
                            // This way for short matches like `views`, the root section of the `Views`
                            // page would have a higher chance of being treated as more relevant than a
                            // subsection where words like `views` appear multiple times and/or in a
                            // string with more tokens, like in `Views > View Cells`.
                            ->addShould(
                                (new QueryStringQuery($queryString->toPrefix()))
                                    ->setFields(['title'])
                                    ->setDefaultOperator('AND')
                                    ->setBoost(4)
                            )
                    )
            );
    }

    /**
     * Formats the results.
     *
     * @param \Elastica\Result[] $results The results.
     * @param mixed[] $options The search options.
     * @return array<mixed[]>
     */
    protected function formatResults(array $results, array $options): array
    {
        return array_map(
            function (Result $result) use ($options): array {
                $data = $result->getData();

                $hierarchy = $data['hierarchy'];

                $highlights = $result->getHighlights();
                $contentsHighlights = $highlights['contents'] ?? [];
                $hierarchyHighlights = [];
                if (isset($highlights['hierarchy'][0])) {
                    // Inject highlighted hierarchy entries by comparing the plain values.
                    // Unfortunately Elasticsearch doesn't provide the position of the matches
                    // in the array (it works with nested documents, but they come with other
                    // problems, like not easily being able to apply query string searches where
                    // `AND` means in _any_ child documents, instead of in _one_ child document).
                    $hierarchyHighlights = $hierarchy;
                    foreach ($highlights['hierarchy'] as $entry) {
                        $plain = str_replace([$options['highlightPreTag'], $options['highlightPostTag']], '', $entry);
                        if ($options['encoder'] === 'html') {
                            $plain = html_entity_decode($plain, ENT_QUOTES);
                        }
                        $index = array_search($plain, $hierarchyHighlights, true);
                        if ($index !== false) {
                            $hierarchyHighlights[$index] = $entry;
                        }
                    }
                }

                $contents = mb_substr((string)$data['contents'], 0, 180);
                if ($data['type'] === 'external') {
                    $contents = $data['url'];
                }

                // Highlights are encoded on Elasticsearch query level, regular fields need
                // to be encoded manually.
                if ($options['encoder'] === 'html') {
                    $hierarchy = array_map('h', $hierarchy);
                    $contents = h($contents);
                }

                return [
                    'url' => $data['url'],
                    'page_url' => $data['page_url'],
                    'level' => $data['level'],
                    'position' => $data['position'],
                    'hierarchy' => $hierarchy,
                    'contents' => $contents,
                    'highlights' => [
                        'contents' => $contentsHighlights,
                        'hierarchy' => $hierarchyHighlights,
                    ],
                ];
            },
            $results
        );
    }
}
