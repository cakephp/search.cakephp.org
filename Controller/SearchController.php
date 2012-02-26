<?php
class SearchController extends AppController {

	public $components = array('RequestHandler');

/**
 * Search the elastic search index.
 */
	public function search() {
		$query = array(
			'query' => array(
				'term' => array(
					'contents' => $this->request->query['q'],
					'lang' => $this->request->query['lang'],
				)
			),
			'fields' => array('url', 'title'),
			'highlight' => array(
				'fields' => array(
					'contents' => array('fragment_size' => 100, 'number_of_fragments' => 5)
				)
			)
		);
		$results = $this->Search->search($query);
		$this->set('results', $results);
		$this->set('_serialize', 'results');
	}
}
