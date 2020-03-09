<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;

class SearchController extends AppController
{
    public function initialize(): void
    {
        $this->loadComponent('RequestHandler');
    }

    /**
     * Search the elastic search index.
     *
     * @return void
     */
    public function search()
    {
        $domains = Configure::read('AccessControlAllowOrigin');
        $this->response->cors($this->request)
            ->allowOrigin($domains)
            ->allowMethods(['GET'])
            ->allowHeaders(['X-CSRF-Token'])
            ->maxAge(300)
            ->build();

        if (empty($this->request->getQuery('lang'))) {
            throw new BadRequestException();
        }
        $lang = $this->request->getQuery('lang');

        $version = $this->request->getQuery('version', '2-2');
        $page = (int)$this->request->getQuery('page', 1);
        $page = max($page, 1);

        $query = $this->request->getQuery('q', '');
        if (count(array_filter(explode(' ', $query))) === 1) {
            $query .= '~';
        }

        $options = [
            'query' => $query,
            'page' => $page,
        ];
        $this->loadModel('Search', 'Elastic');
        $results = $this->Search->search($lang, $version, $options);

        $this->viewBuilder()
            ->setClassName('Json')
            ->setOption('serialize', 'results');

        $this->set('results', $results);
    }
}
