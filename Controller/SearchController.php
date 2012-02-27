<?php
class SearchController extends AppController {

	public $components = array('RequestHandler');

/**
 * Search the elastic search index.
 */
	public function search() {
		if (empty($this->request->query['lang'])) {
			throw BadRequestException();
		}
		$lang = $this->request->query['lang'];

		$query = array(
			'query' => array(
				'text' => array(
					'contents' => array(
						'query' => $this->request->query['q'],
						'type' => 'phrase'
					),
				),
			),
			'fields' => array('url'),
			'highlight' => array(
				'fields' => array(
					'contents' => array('fragment_size' => 100, 'number_of_fragments' => 3)
				)
			)
		);
		$results = $this->Search->find($lang, $query);
		$this->set('results', $results);
		$this->set('_serialize', 'results');
	}
}
