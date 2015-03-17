<?php
class SearchController extends AppController {

	public $components = array('RequestHandler');

/**
 * Search the elastic search index.
 */
	public function search() {
		foreach (Configure::read('AccessControlAllowOrigin') as $domain) {
			header("Access-Control-Allow-Origin: $domain", false);
		}

		$version = '2-2';
		if (!empty($this->request->query['version'])) {
			$version = $this->request->query['version'];
		}
		if (empty($this->request->query['lang'])) {
			throw new BadRequestException();
		}
		$lang = $this->request->query['lang'];

		$page = 0;
		if (!empty($this->request->query['page'])) {
			$page = $this->request->query['page'];
		}
		$q = '';
		if (isset($this->request->query['q'])) {
			$q = $this->request->query['q'];
		}

		if (count(array_filter(explode(' ', $q))) === 1) {
			$q .= '~';
		}

		$query = array(
			'query' => array(
				'query_string' => array(
					'fields' => array('contents', 'title^3'),
					'query' => $q,
					'phrase_slop' => 2,
					'default_operator' => 'AND',
					'fuzzy_min_sim' => 0.7
				),
			),
			'fields' => array('url', 'title'),
			'highlight' => array(
				'pre_tags' => array(''),
				'post_tags' => array(''),
				'fields' => array(
					'contents' => array(
						'fragment_size' => 100,
						'number_of_fragments' => 3
					),
				),
			),
			'size' => 25,
		);

		// Pagination
		if ($page > 0) {
			$query['from'] = $query['size'] * ($page - 1);
		}
		$results = $this->Search->find($lang, $version, $query);
		$this->set('results', $results);
		$this->set('_serialize', 'results');
	}
}
