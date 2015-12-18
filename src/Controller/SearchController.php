<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Network\Exception\BadRequestException;

class SearchController extends AppController
{

    /**
     * @var array Components
     */
    public $components = [
        'RequestHandler'
    ];

    /**
     * Search the elastic search index.
     *
     * @return void
     */
    public function search()
    {
        $referer = $this->request->referer();
        $origin = $this->request->header('Origin');
        foreach (Configure::read('AccessControlAllowOrigin') as $domain) {
            if (strpos($referer, $domain) === 0 || strpos($origin, $domain) === 0) {
                $this->response->header(['Access-Control-Allow-Origin' => $domain]);
                break;
            }
        }
        $version = '2-2';
        if (!empty($this->request->query['version'])) {
            $version = $this->request->query['version'];
        }
        if (empty($this->request->query['lang'])) {
            throw new BadRequestException();
        }
        $lang = $this->request->query['lang'];

        $page = 1;
        if (!empty($this->request->query['page'])) {
            $page = $this->request->query['page'];
        }
        $page = max($page, 1);

        if (count(array_filter(explode(' ', $this->request->query['q']))) === 1) {
            $this->request->query['q'] .= '~';
        }

        $options = [
            'query' => $this->request->query('q'),
            'page' => $page,
        ];
        $this->loadModel('Search', 'Elastic');
        $results = $this->Search->search($lang, $version, $options);

        $this->viewBuilder()->className('Json');
        $this->set('results', $results);
        $this->set('_serialize', 'results');
    }
}
