<?php
declare(strict_types=1);

namespace App\QueryTranslation;

use App\QueryTranslation\Generators\QueryString\Word;
use Exception;
use QueryTranslator\Languages\Galach\Generators\Common\Aggregate;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Group;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\LogicalAnd;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\LogicalNot;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\LogicalOr;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Mandatory;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Phrase;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Prohibited;
use QueryTranslator\Languages\Galach\Generators\Lucene\Common\Query;
use QueryTranslator\Languages\Galach\Generators\QueryString as QueryStringGenerator;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\TokenExtractor\Text;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Node\LogicalNot as LogicalNotNode;
use QueryTranslator\Languages\Galach\Values\Node\Prohibited as ProhibitedNode;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Phrase as PhraseToken;
use QueryTranslator\Languages\Galach\Values\Token\Word as WordToken;
use QueryTranslator\Values\SyntaxTree;

/**
 * Query string parser and compiler.
 */
class QueryString
{
    /**
     * @var \QueryTranslator\Values\SyntaxTree
     */
    protected $syntaxTree;

    /**
     * Constructor.
     *
     * @param string $query The simple search query string to parse.
     */
    public function __construct(string $query)
    {
        $query = (string)preg_replace('/[\x00\xA0]/u', '', $query);

        $tokenExtractor = new Text();
        $tokenizer = new Tokenizer($tokenExtractor);
        $tokenSequence = $tokenizer->tokenize($query);

        $parser = new Parser();
        $this->syntaxTree = $parser->parse($tokenSequence);

        $this->sanitizeTerms($this->syntaxTree);
    }

    /**
     * Extracts all matchable terms.
     *
     * A matchable term is a term that is not prohibited or excluded via
     * a logical NOT operator.
     *
     * @return string[]
     */
    public function extractMatchableTerms(): array
    {
        $extract = function (array $nodes) use (&$extract): array {
            $terms = [];

            foreach ($nodes as $node) {
                /** @var \QueryTranslator\Values\Node $node */
                if (
                    $node instanceof ProhibitedNode ||
                    $node instanceof LogicalNotNode
                ) {
                    continue;
                }
                if (
                    $node instanceof Term &&
                    $node->token instanceof WordToken
                ) {
                    $terms[] = $node->token->word;
                }
                if (
                    $node instanceof Term &&
                    $node->token instanceof PhraseToken
                ) {
                    $terms[] = $node->token->phrase;
                }

                $terms = array_merge($terms, $extract($node->getNodes()));
            }

            return $terms;
        };

        return $extract($this->syntaxTree->rootNode->getNodes());
    }

    /**
     * Extracts all terms.
     *
     * @return string[]
     */
    public function extractTerms(): array
    {
        $extract = function (array $nodes) use (&$extract): array {
            $terms = [];

            foreach ($nodes as $node) {
                /** @var \QueryTranslator\Values\Node $node */
                if (
                    $node instanceof Term &&
                    $node->token instanceof WordToken
                ) {
                    $terms[] = $node->token->word;
                }
                if (
                    $node instanceof Term &&
                    $node->token instanceof PhraseToken
                ) {
                    $terms[] = $node->token->phrase;
                }

                $terms = array_merge($terms, $extract($node->getNodes()));
            }

            return $terms;
        };

        return $extract($this->syntaxTree->rootNode->getNodes());
    }

    /**
     * Returns the number of terms.
     *
     * @return int
     */
    public function getTermCount(): int
    {
        return count($this->extractTerms());
    }

    /**
     * Returns the length of the shortest term.
     *
     * @return int
     */
    public function getShortestTermLength(): int
    {
        $terms = $this->extractTerms();
        $length = PHP_INT_MAX;

        foreach ($terms as $term) {
            $length = min($length, mb_strlen($term));
        }

        return $length;
    }

    /**
     * Returns whether the query string can be compiled without errors.
     *
     * @return bool
     */
    public function isCompilable(): bool
    {
        try {
            $this->compile($this->syntaxTree);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Compiles the query string with fuzzy modifiers on its terms.
     *
     * @return string
     */
    public function toFuzzy(): string
    {
        return $this->compile($this->syntaxTree, '~');
    }

    /**
     * Compiles the query string with prefix modifiers on its terms.
     *
     * @return string
     */
    public function toPrefix(): string
    {
        return $this->compile($this->syntaxTree, '*');
    }

    /**
     * Compiles the query string.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->compile($this->syntaxTree);
    }

    /**
     * Sanitizes the terms.
     *
     * @param \QueryTranslator\Values\SyntaxTree $syntaxTree The syntax tree holding the terms.
     * @return void
     */
    protected function sanitizeTerms(SyntaxTree $syntaxTree)
    {
        $traverse = function (array $nodes) use (&$traverse): void {
            foreach ($nodes as $node) {
                /** @var \QueryTranslator\Values\Node $node */
                if (
                    $node instanceof Term &&
                    $node->token instanceof WordToken
                ) {
                    $node->token->word = (string)preg_replace('/([^*~])[*~]*$/', '\\1', $node->token->word);
                    $node->token->word = (string)preg_replace('/^[*~]*([^*~])/', '\\1', $node->token->word);
                }

                $traverse($node->getNodes());
            }
        };
        $traverse($syntaxTree->rootNode->getNodes());
    }

    /**
     * Compiles the syntax tree to a string.
     *
     * @param \QueryTranslator\Values\SyntaxTree $syntaxTree The syntax tree to compile.
     * @param string $termModifier The modifier to apply to the terms.
     * @return string
     */
    protected function compile(SyntaxTree $syntaxTree, string $termModifier = ''): string
    {
        $generator = new QueryStringGenerator(
            new Aggregate([
                new Query(),
                new Word($termModifier),
                new Mandatory(),
                new Prohibited(),
                new Group(),
                new Phrase(),
                new LogicalAnd(),
                new LogicalOr(),
                new LogicalNot(),
            ])
        );

        return $generator->generate($syntaxTree);
    }
}
