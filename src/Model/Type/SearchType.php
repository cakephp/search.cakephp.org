<?php
namespace App\Model\Type;

use Cake\ElasticSearch\Type;
use Elastica\Query\QueryString;

class SearchType extends Type {

/**
 * Search the index
 */
	public function search($lang, $version, $options = []) {
		$options += array(
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
			->page($options['page'], 25)
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
