<?php
$querystringArgumentAppender = function($url, $query) {
    $parsedUrl = parse_url($url);
    if (!isset($parsedUrl['path']) || $parsedUrl['path'] == null) {
        $url .= '/';
    }
    $separator = (!isset($parsedUrl['query']) || $parsedUrl['query'] == NULL) ? '?' : '&';
    $url .= $separator . $query;
    return $url;
};

return [
    'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),

    'Datasources' => [
        'elastic' => [
            'url' => $querystringArgumentAppender(
                env('DOKKU_ELASTICSEARCH_AQUA_URL'),
                'driver=Cake\ElasticSearch\Datasource\Connection&className=Cake\ElasticSearch\Datasource\Connection'
            ),
        ],
        'test_elastic' => [
            'url' => $querystringArgumentAppender(
                env('DOKKU_ELASTICSEARCH_AQUA_URL'),
                'driver=Cake\ElasticSearch\Datasource\Connection&className=Cake\ElasticSearch\Datasource\Connection'
            ),
        ],
    ]
];
