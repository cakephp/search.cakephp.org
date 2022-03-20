<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Index\SearchIndex;
use App\QueryTranslation\QueryString;
use Cake\ElasticSearch\IndexRegistry;
use Cake\Http\Exception\BadRequestException;

class SearchController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->loadComponent('RequestHandler');
    }

    /**
     * Search the elastic search index.
     *
     * @return void
     */
    public function search(): void
    {
        $lang = $this->request->getQuery('lang', '');
        if (!is_string($lang)) {
            $e = new BadRequestException();
            $e->setHeader('X-Reason', 'invalid-language');

            throw $e;
        }
        $lang = trim($lang);
        if (!$lang) {
            $e = new BadRequestException();
            $e->setHeader('X-Reason', 'missing-language');

            throw $e;
        }

        $version = $this->request->getQuery('version', '');
        if (!is_string($version)) {
            $e = new BadRequestException();
            $e->setHeader('X-Reason', 'invalid-version');

            throw $e;
        }
        $version = $this->getSearchVersion($version);
        if (!$version) {
            $e = new BadRequestException();
            $e->setHeader('X-Reason', 'missing-version');

            throw $e;
        }

        $query = $this->request->getQuery('q', '');
        if (!is_string($query)) {
            $e = new BadRequestException();
            $e->setHeader('X-Reason', 'invalid-query');

            throw $e;
        }

        $queryString = new QueryString($query);

        if (
            !$queryString->isCompilable() ||
            !$queryString->getTermCount() ||
            $queryString->getShortestTermLength() < 3
        ) {
            $e = new BadRequestException();
            $e->setHeader('X-Reason', 'invalid-syntax');

            throw $e;
        }

        $page = (int)$this->request->getQuery('page', 1);
        $limit = (int)$this->request->getQuery('limit', 10);

        $highlightPreTag = $this->request->getQuery('highlightPreTag', '{{');
        $highlightPostTag = $this->request->getQuery('highlightPostTag', '}}');

        // HTML encoding helps to prevent code examples from the docs being
        // interpreted as HTML when displayed in an HTML environment.
        $encoder = 'default';
        if ($this->request->getQuery('encoder') === 'html') {
            $encoder = 'html';
        }

        $options = [
            'page' => $page,
            'limit' => $limit,
            'highlightPreTag' => $highlightPreTag,
            'highlightPostTag' => $highlightPostTag,
            'encoder' => $encoder,
        ];
        $index = IndexRegistry::get('Search');
        assert($index instanceof SearchIndex);
        $results = $index->search($lang, $version, $queryString, $options);

        $this
            ->viewBuilder()
            ->setClassName('Json')
            ->setOption('serialize', 'results');

        $this->set('results', $results);
    }

    /**
     * Get the search version. Account for backwards compatible names
     * as book rebuilds take some time.
     *
     * @param string$version The request version to transform into the search version.
     * @return string
     */
    protected function getSearchVersion(string $version): string
    {
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
