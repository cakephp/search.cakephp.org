<?php
declare(strict_types=1);

namespace App\QueryTranslation\Generators\QueryString;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\WordBase;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Word as WordToken;
use QueryTranslator\Values\Node;

/**
 * Word node visitor.
 */
class Word extends WordBase
{
    /**
     * @var string
     */
    protected $modifier;

    /**
     * Constructor.
     *
     * @param string $modifier The modifier to append to the word.
     */
    public function __construct(string $modifier = '')
    {
        parent::__construct();

        $this->modifier = $modifier;
    }

    /**
     * @inheritDoc
     */
    public function accept(Node $node)
    {
        return $node instanceof Term && $node->token instanceof WordToken;
    }

    /**
     * @inheritDoc
     */
    public function visit(Node $node, ?Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof Term) {
            throw new LogicException(
                'Implementation accepts instance of Term Node'
            );
        }

        $token = $node->token;

        if (!$token instanceof WordToken) {
            throw new LogicException(
                'Implementation accepts instance of Word Token'
            );
        }

        $word = $this->escapeWord($token->word);
        $word .= $this->modifier;

        return $word;
    }

    /**
     * @inheritDoc
     */
    protected function escapeWord($string)
    {
        return (string)preg_replace('/([' . preg_quote('+-&|!(){}[]^"~*?:\\/ ', '/') . '])/', '\\\\$1', $string);
    }
}
