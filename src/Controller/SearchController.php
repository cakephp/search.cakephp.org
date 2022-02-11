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
        $this->response = $this->response->cors($this->request)
            ->allowOrigin($domains)
            ->allowMethods(['GET'])
            ->allowHeaders(['X-CSRF-Token'])
            ->maxAge(300)
            ->build();

        if (empty($this->request->getQuery('lang'))) {
            throw new BadRequestException();
        }
        $lang = $this->request->getQuery('lang');

        $version = $this->getVersion();
        $page = (int)$this->request->getQuery('page', 1);
        $page = max($page, 1);
        $limit = (int)$this->request->getQuery('limit', 25);

        $query = $this->request->getQuery('q', '');
        if (count(array_filter(explode(' ', $query))) === 1) {
            $query .= '~';
        }

        $options = [
            'query' => $query,
            'page' => $page,
            'limit' => $limit,
        ];
        $this->loadModel('Search', 'Elastic');
        $results = $this->Search->search($lang, $version, $options);

        $this->viewBuilder()
            ->setClassName('Json')
            ->setOption('serialize', 'results');

        $this->set('results', $results);
    }

    /**
     * Get the search version. Account for backwards compatible names
     * as book rebuilds take some time.
     */
    protected function getVersion()
    {
        $version = $this->request->getQuery('version');
        switch ($version) {
            case 'authorization-11':
                return 'authorization-1';

            case 'authentication-11':
                return 'authentication-1';

            case '1-1':
                return '11';

            case '1-2':
                return '12';

            case '1-3':
                return '13';

            case '3-0':
                return '30';

            case '4-0':
                return '40';

            case '2-10':
            case '2-2':
                return '20';
            default:
                return $version;
        }
    }
}
