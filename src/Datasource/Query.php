<?php
declare(strict_types=1);

namespace App\Datasource;

use Cake\ElasticSearch\Index;
use Cake\ElasticSearch\Query as BaseQuery;

/**
 * Adds functionality to the base elastic search plugin query.
 *
 * @method \Cake\ElasticSearch\ResultSet all()
 */
class Query extends BaseQuery
{
    /**
     * @inheritDoc
     */
    public function __construct(Index $repository)
    {
        parent::__construct($repository);

        $this->_queryParts['params'] = [];
    }

    /**
     * Enables field collapsing.
     *
     * @param string $field The field to collapse on.
     * @param mixed[] $collapse The collapse configuration.
     * @return $this
     */
    public function collapse(string $field, array $collapse = [])
    {
        $this->param('collapse', compact('field') + $collapse);

        return $this;
    }

    /**
     * Sets a parameter.
     *
     * @param string $key The parameter name.
     * @param mixed $value The parameter value.
     * @return $this
     */
    public function param(string $key, $value)
    {
        $this->_queryParts['params'][$key] = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function compileQuery()
    {
        foreach ($this->_queryParts['params'] as $key => $value) {
            $this->_elasticQuery->setParam($key, $value);
        }

        return parent::compileQuery();
    }
}
