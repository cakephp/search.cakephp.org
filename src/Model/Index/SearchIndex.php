<?php
namespace App\Model\Index;

use Cake\ElasticSearch\Index;
use Elastica\Query\QueryString;

class SearchIndex extends Index
{
    protected const MAX_LIMIT = 25;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setType('_doc');
    }

    /**
     * Search the index
     *
     * @return array
     */
    public function search($lang, $version, $options = [])
    {
        $options += [
            'query' => '',
            'page' => 1,
            'sort' => ['_score'],
            'limit' => 25,
        ];
        // Set the index and type name up.
        $indexName = implode('-', ['cake-docs', $version, $lang]);

        // This is a bit dangerous, but this class only has one real method.
        $this->setName($indexName);
        $query = $this->query();
        $limit = $options['limit'] >= 1 && $options['limit'] <= self::MAX_LIMIT ? $options['limit'] : self::MAX_LIMIT;

        $query->page($options['page'], $limit)
            ->highlight([
                'pre_tags' => [''],
                'post_tags' => [''],
                'fields' => [
                    'contents' => [
                        'fragment_size' => 100,
                        'number_of_fragments' => 3
                    ],
                ],
            ])
            ->queryMust(function () use ($options) {
                $q = new QueryString($options['query']);
                $q
                    ->setPhraseSlop(2)
                    ->setFields(['contents', 'title^3'])
                    ->setDefaultOperator('AND');

                return $q;
            })
            ->order($options['sort']);

        /** @var \Cake\ElasticSearch\ResultSet $results  */
        $results = $query->all();
        $rows = $results->map(function ($row) {
            $contents = '';
            if ($row->highlights()) {
                $contents = $row->highlights()['contents'];
            }
            return [
                'title' => $row->title ?: '',
                'url' => $row->url,
                'contents' => $contents,
            ];
        });

        return [
            'page' => $options['page'] ?: 1,
            'total' => $results->getTotalHits(),
            'data' => $rows,
        ];
    }
}
