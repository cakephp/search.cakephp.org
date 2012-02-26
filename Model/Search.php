<?php
App::uses('HttpSocket', 'Network/Http');

class Search {
/**
 * Search the index
 */
	public function search($query = array()) {
		$query += array(
			'query' => array(),
			'sort' => array('_search'),
		);
		$config = Configure::read('ElasticSearch');
		$Http = new HttpSocket();
		$results = $Http->get($url, array(), array('body' => json_encode($query)));
		$contents = json_decode($results->body());
		return array_map(function ($el) {
			return array(
				'contents' => $el['highlight']['contents'],
				'url' => $el['fields']['url']
			);
		}, $contents);
	}
}
