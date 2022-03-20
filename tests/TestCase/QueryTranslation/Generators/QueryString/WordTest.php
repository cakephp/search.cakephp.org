<?php
declare(strict_types=1);

namespace App\Test\TestCase\QueryTranslation\Generators\QueryString;

use App\QueryTranslation\Generators\QueryString\Word;
use Cake\TestSuite\TestCase;
use LogicException;
use QueryTranslator\Languages\Galach\Values\Node\Mandatory;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Word as WordToken;
use QueryTranslator\Values\Token;

class WordTest extends TestCase
{
    public function testAcceptValidNode(): void
    {
        $word = new Word();
        $this->assertTrue($word->accept(new Term(new WordToken('lexeme', 1, 'domain', 'word'))));
    }

    public function testAcceptInvalidNode(): void
    {
        $word = new Word();
        $this->assertFalse($word->accept(new Mandatory()));
        $this->assertFalse($word->accept(new Term(new Token('type', 'lexeme', 1))));
    }

    public function testVisitValidNode(): void
    {
        $word = new Word('modifier');
        $this->assertSame(
            '\+\-\&\|\!\(\)\{\}\[\]\^\"\~\*\?\:\\\\\/\ modifier',
            $word->visit(new Term(new WordToken('lexeme', 1, 'domain', '+-&|!(){}[]^"~*?:\\/ ')))
        );
    }

    public function testVisitInvalidNodeType(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Implementation accepts instance of Term Node');

        $word = new Word();
        $word->visit(new Mandatory());
    }

    public function testVisitInvalidTokenType(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Implementation accepts instance of Word Token');

        $word = new Word();
        $word->visit(new Term(new Token('type', 'lexeme', 1)));
    }
}
