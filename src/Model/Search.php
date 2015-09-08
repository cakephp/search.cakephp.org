<?php
namespace Model;

use Cake\ElasticSearch\Type;
use Elastica\Query\QueryString;

class Search extends Type {

/**
 * Search the index
 */
	public function search($lang, $version, $options = []) {
		$query += array(
			'query' => '',
			'page' => 1,
			'sort' => array('_score'),
		);
		$typeName = implode('-', [$version, $lang]);

		// This is a bit dangerous, but this class only has one real method.
		$this->name($typeName);
		$query = $this->query();

		$q = new QueryString($options['query']);
		$q->setPhraseSlop(2)
			->setFields(['contents', 'title^3'])
			->setDefaultOperator('AND')
			->setFuzzyMinSim('0.7');

		$query->select(['url', 'title'])
			->limit(25)
			->page($options['page'])
			->highlight([
				'pre_tags' => array(''),
				'post_tags' => array(''),
				'fields' => array(
					'contents' => array(
						'fragment_size' => 100,
						'number_of_fragments' => 3
					),
				),
			])
			->query($q);

		$results = $query->all();
		$rows = $results->map(function ($row) {
			return [
				'title' => $row->title ?: '',
				'url' => $row->url,
				'contents' => $row->highlights()['contents'],
			];
		});
		return [
			'page' => $options['page'] ?: 1,
			'total' => $results->count(),
			'data' => $rows,
		];
	}
}
