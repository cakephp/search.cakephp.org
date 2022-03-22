<?php
declare(strict_types=1);

namespace App\Test\TestCase\QueryTranslation;

use App\QueryTranslation\QueryString;
use Cake\TestSuite\TestCase;
use RuntimeException;

class QueryStringTest extends TestCase
{
    public function testExtractMatchableTerms(): void
    {
        $queryString = new QueryString('"foo bar" NOT baz but -no (yes !nah (deep))');

        $this->assertSame(['foo bar', 'but', 'yes', 'deep'], $queryString->extractMatchableTerms());
    }

    public function testExtractTerms(): void
    {
        $queryString = new QueryString('"foo bar" NOT baz but -no (yes !nah (deep))');

        $this->assertSame(['foo bar', 'baz', 'but', 'no', 'yes', 'nah', 'deep'], $queryString->extractTerms());
    }

    public function testGetTermCount(): void
    {
        $queryString = new QueryString('"foo bar" NOT baz but -no (yes !nah (deep))');

        $this->assertSame(7, $queryString->getTermCount());
    }

    public function testGetShortestTermLength(): void
    {
        $queryString = new QueryString('foo foobar foobarbaz');

        $this->assertSame(3, $queryString->getShortestTermLength());
    }

    public function testIsCompilable(): void
    {
        $queryString = new QueryString('query');

        $this->assertTrue($queryString->isCompilable());
    }

    public function testIsNotCompilable(): void
    {
        $queryString = $this
            ->getMockBuilder(QueryString::class)
            ->setConstructorArgs(['query'])
            ->onlyMethods(['compile'])
            ->getMock();

        $queryString
            ->expects($this->once())
            ->method('compile')
            ->willThrowException(new RuntimeException());

        $this->assertFalse($queryString->isCompilable());
    }

    public function testToFuzzy(): void
    {
        $queryString = new QueryString('"foo bar" NOT baz but -no (yes !nah (deep))');

        $this->assertSame(
            '"foo bar" NOT baz~ but~ -no~ (yes~ NOT nah~ (deep~))',
            $queryString->toFuzzy()
        );
    }

    public function testToPrefix(): void
    {
        $queryString = new QueryString('"foo bar" NOT baz but -no (yes !nah (deep))');

        $this->assertSame(
            '"foo bar" NOT baz* but* -no* (yes* NOT nah* (deep*))',
            $queryString->toPrefix()
        );
    }

    public function testToString(): void
    {
        $queryString = new QueryString('"foo bar" NOT baz but -no (yes !nah (deep))');

        $this->assertSame(
            '"foo bar" NOT baz but -no (yes NOT nah (deep))',
            $queryString->toString()
        );
    }

    public function testCompileComplexQuery(): void
    {
        $queryString = new QueryString(
            '-("xml - +(view)" (+sql ) *** OR **orm~~) user:name |*~\  +"foo bar"    AND' . "\0" . '  "   " "   " +-'
        );

        $this->assertSame(
            '-("xml - +(view)" (+sql) \*\*\* OR orm) user\:name \|\*\~\  +"foo bar" AND "   " "   "',
            $queryString->toString()
        );
    }
}
