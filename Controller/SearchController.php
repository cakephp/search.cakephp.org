<?php
class SearchController extends AppController {

	public $components = array('RequestHandler');

/**
 * Search the elastic search index.
 */
	public function search() {
		if (empty($this->request->query['lang'])) {
			throw new BadRequestException();
		}
		$lang = $this->request->query['lang'];

		$page = 0;
		if (!empty($this->request->query['page'])) {
			$page = $this->request->query['page'];
		}

		$query = array(
			'query' => array(
				'text' => array(
					'contents' => array(
						'query' => $this->request->query['q'],
						'type' => 'phrase'
					),
				),
			),
			'fields' => array('url', 'title'),
			'highlight' => array(
				'fields' => array(
					'contents' => array('fragment_size' => 100, 'number_of_fragments' => 3)
				)
			),
			'size' => 25,
		);

		// Pagination
		if ($page > 0) {
			$query['from'] = $query['size'] * ($page - 1);
		}
		$results = $this->Search->find($lang, $query);
		$this->set('results', $results);
		$this->set('_serialize', 'results');
	}
}
