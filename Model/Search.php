<?php
App::uses('HttpSocket', 'Network/Http');

class Search {
/**
 * Search the index
 */
	public function find($lang, $query = array()) {
		$query += array(
			'query' => array(),
			'sort' => array('_score'),
		);
		$config = Configure::read('ElasticSearch');
		$url = $config['url'];
		$url .= $lang . '/_search';

		$Http = new HttpSocket();
		$results = $Http->get($url, array(), array('body' => json_encode($query)));
		$contents = json_decode($results->body(), true);
		$data = array_map(function ($el) {
			return array(
				'title' => isset($el['fields']['title']) ? $el['fields']['title'] : '',
				'url' => $el['fields']['url'],
				'contents' => str_replace(array('<em>', '</em>'), $el['highlight']['contents']),
			);
		}, $contents['hits']['hits']);
		return array(
			'page' => isset($query['from']) ? $query['from'] : 1,
			'total' => $contents['hits']['total'],
			'data' => $data
		);
	}
}
