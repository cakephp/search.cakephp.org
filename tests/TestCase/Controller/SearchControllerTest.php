<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Index\SearchIndex;
use Cake\ElasticSearch\IndexRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SearchControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected $fixtures = [
        'app.Search',
    ];

    public function setUp(): void
    {
        parent::setUp();
        IndexRegistry::clear();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        IndexRegistry::clear();
    }

    /**
     * Asserts content in the response body equals.
     *
     * @param mixed $expected The content to check for.
     * @return void
     */
    protected function assertJsonResponseEquals($expected): void
    {
        $this->assertSame(
            json_encode(
                $expected,
                JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS |
                JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR
            ),
            $this->_getBodyAsString()
        );
    }

    public function testInvalidOptionsRequest(): void
    {
        $this->configRequest([
            'environment' => [
                'HTTPS' => 'on',
            ],
            'headers' => [
                'Origin' => 'https://invalid.origin',
            ],
        ]);

        $this->options('/search');

        $this->assertResponseOk();
        $this->assertResponseEmpty();
        $this->assertSame(['Content-Type'], array_keys($this->_response->getHeaders()));
    }

    public function testValidOptionsRequest(): void
    {
        $this->configRequest([
            'environment' => [
                'HTTPS' => 'on',
            ],
            'headers' => [
                'Origin' => 'https://book.cakephp.org',
            ],
        ]);

        $this->options('/search');

        $this->assertResponseOk();
        $this->assertResponseEmpty();
        $this->assertHeader('Access-Control-Allow-Origin', 'https://book.cakephp.org');
        $this->assertHeader('Access-Control-Allow-Methods', 'GET');
        $this->assertHeader('Access-Control-Allow-Headers', 'X-CSRF-Token');
        $this->assertHeader('Access-Control-Expose-Headers', 'X-Reason');
        $this->assertHeader('Access-Control-Max-Age', '300');
    }

    public function testMissingLang(): void
    {
        $this->get('/search');

        $this->assertResponseError();
        $this->assertHeader('X-Reason', 'missing-language');
    }

    public function testInvalidLang(): void
    {
        $this->get('/search?lang[]=foo&lang[]=bar');

        $this->assertResponseError();
        $this->assertHeader('X-Reason', 'invalid-language');
    }

    public function testMissingVersion(): void
    {
        $this->get('/search?lang=en');

        $this->assertResponseError();
        $this->assertHeader('X-Reason', 'missing-version');
    }

    public function testInvalidVersion(): void
    {
        $this->get('/search?lang=en&version[]=foo&version[]=bar');

        $this->assertResponseError();
        $this->assertHeader('X-Reason', 'invalid-version');
    }

    public function testInvalidQuery(): void
    {
        $this->get('/search?lang=en&version=test&q[]=foo&q[]=bar');

        $this->assertResponseError();
        $this->assertHeader('X-Reason', 'invalid-query');
    }

    public function testInvalidTermCount(): void
    {
        $this->get('/search?lang=en&version=test&q=');

        $this->assertResponseError();
        $this->assertHeader('X-Reason', 'invalid-syntax');
    }

    public function testInvalidTermLength(): void
    {
        $this->get('/search?lang=en&version=test&q=ab');

        $this->assertResponseError();
        $this->assertHeader('X-Reason', 'invalid-syntax');
    }

    public function testHighlightTags(): void
    {
        $this->get('/search?lang=en&version=test&limit=1&highlightPreTag=<em>&highlightPostTag=</em>&q=subsection');

        $this->assertResponseOk();
        $this->assertJsonResponseEquals([
            'page' => 1,
            'total' => 2,
            'data' => [
                [
                    'url' => '/test/1/test.html#level-1-subsection-1-title',
                    'page_url' => '/test/1/test.html',
                    'level' => 1,
                    'position' => 1,
                    'hierarchy' => [
                        'Test',
                        'Level 1 Subsection 1 <b>Title</b>',
                    ],
                    'contents' =>
                        'Foo\Bar\Baz::method(string $argument) $argument - Method argument description. ' .
                        'Level 1 subsection 1 <b>content</b>: $foo = new \Foo\Bar\Baz(); ' .
                        '$value = $foo->method(\'argument\'); Lo',
                    'highlights' => [
                        'contents' => [
                            'Level 1 <em>subsection</em> 1 <b>content</b>: $foo = new \Foo\Bar\Baz(',
                        ],
                        'hierarchy' => [
                            'Test',
                            'Level 1 <em>Subsection</em> 1 <b>Title</b>',
                        ],
                    ],
                ],
            ],
            'terms' => [
                'subsection',
            ],
        ]);
    }

    public function testHtmlEncoder(): void
    {
        $this->get(
            '/search?lang=en&version=test&limit=1&highlightPreTag=<em>&highlightPostTag=</em>&encoder=html&q=subsection'
        );

        $this->assertResponseOk();
        $this->assertJsonResponseEquals([
            'page' => 1,
            'total' => 2,
            'data' => [
                [
                    'url' => '/test/1/test.html#level-1-subsection-1-title',
                    'page_url' => '/test/1/test.html',
                    'level' => 1,
                    'position' => 1,
                    'hierarchy' => [
                        'Test',
                        'Level 1 Subsection 1 &lt;b&gt;Title&lt;/b&gt;',
                    ],
                    'contents' =>
                        'Foo\Bar\Baz::method(string $argument) $argument - Method argument description. ' .
                        'Level 1 subsection 1 &lt;b&gt;content&lt;/b&gt;: $foo = new \Foo\Bar\Baz(); ' .
                        '$value = $foo-&gt;method(&#039;argument&#039;); Lo',
                    'highlights' => [
                        'contents' => [
                            'Level 1 <em>subsection</em> 1 &lt;b&gt;content&lt;&#x2F;b&gt;: $foo = new \Foo\Bar\Baz(',
                        ],
                        'hierarchy' => [
                            'Test',
                            'Level 1 <em>Subsection</em> 1 &lt;b&gt;Title&lt;&#x2F;b&gt;',
                        ],
                    ],
                ],
            ],
            'terms' => [
                'subsection',
            ],
        ]);
    }

    public function testPagination(): void
    {
        $this->get('/search?lang=en&version=test&limit=2&page=1&q=-non_existent_term_to_find_everything');
        $this->assertJsonResponseEquals([
            'page' => 1,
            'total' => 8,
            'data' => [
                [
                    'url' => '/test/1/test.html#test',
                    'page_url' => '/test/1/test.html',
                    'level' => 0,
                    'position' => 0,
                    'hierarchy' => [
                        'Test',
                    ],
                    'contents' => 'class Foo\Bar\Baz Test root section content.',
                    'highlights' => [
                        'contents' => [],
                        'hierarchy' => [],
                    ],
                ],
                [
                    'url' => 'https://example.com/foo',
                    'page_url' => 'https://example.com/foo',
                    'level' => 0,
                    'position' => 0,
                    'hierarchy' => [
                        'Foo',
                    ],
                    'contents' => 'https://example.com/foo',
                    'highlights' => [
                        'contents' => [],
                        'hierarchy' => [],
                    ],
                ],
            ],
            'terms' => [],
        ]);

        $this->get('/search?lang=en&version=test&limit=2&page=2&q=-non_existent_term_to_find_everything');
        $this->assertJsonResponseEquals([
            'page' => 2,
            'total' => 8,
            'data' => [
                [
                    'url' => 'https://example.com/baz',
                    'page_url' => 'https://example.com/baz',
                    'level' => 0,
                    'position' => 0,
                    'hierarchy' => [
                        'Baz',
                    ],
                    'contents' => 'https://example.com/baz',
                    'highlights' => [
                        'contents' => [],
                        'hierarchy' => [],
                    ],
                ],
                [
                    'url' => '/test/1/test/nested.html#nested',
                    'page_url' => '/test/1/test/nested.html',
                    'level' => 0,
                    'position' => 0,
                    'hierarchy' => [
                        'Test',
                        'Nested',
                    ],
                    'contents' => 'Nested root section content.',
                    'highlights' => [
                        'contents' => [],
                        'hierarchy' => [],
                    ],
                ],
            ],
            'terms' => [],
        ]);

        $this->get('/search?lang=en&version=test&limit=2&page=1000&q=-non_existent_term_to_find_everything');
        $this->assertJsonResponseEquals([
            'page' => 1000,
            'total' => 8,
            'data' => [],
            'terms' => [],
        ]);
    }

    public function testSearch(): void
    {
        $this->get('/search?lang=en&version=test&q=subsection');

        $this->assertResponseOk();
        $this->assertJsonResponseEquals([
            'page' => 1,
            'total' => 2,
            'data' => [
                [
                    'url' => '/test/1/test.html#level-1-subsection-1-title',
                    'page_url' => '/test/1/test.html',
                    'level' => 1,
                    'position' => 1,
                    'hierarchy' => [
                        'Test',
                        'Level 1 Subsection 1 <b>Title</b>',
                    ],
                    'contents' =>
                        'Foo\Bar\Baz::method(string $argument) $argument - Method argument description. ' .
                        'Level 1 subsection 1 <b>content</b>: $foo = new \Foo\Bar\Baz(); ' .
                        '$value = $foo->method(\'argument\'); Lo',
                    'highlights' => [
                        'contents' => [
                            'Level 1 {{subsection}} 1 <b>content</b>: $foo = new \Foo\Bar\Baz(',
                        ],
                        'hierarchy' => [
                            'Test',
                            'Level 1 {{Subsection}} 1 <b>Title</b>',
                        ],
                    ],
                ],
                [
                    'url' => '/test/1/test/nested.html#level-1-subsection-1-title',
                    'page_url' => '/test/1/test/nested.html',
                    'level' => 1,
                    'position' => 1,
                    'hierarchy' => [
                        'Test',
                        'Nested',
                        'Level 1 Subsection 1 Title',
                    ],
                    'contents' => 'Level 1 subsection 1 content.',
                    'highlights' => [
                        'contents' => [
                            0 => 'Level 1 {{subsection}} 1 content.',
                        ],
                        'hierarchy' => [
                            'Test',
                            'Nested',
                            'Level 1 {{Subsection}} 1 Title',
                        ],
                    ],
                ],
            ],
            'terms' => [
                'subsection',
            ],
        ]);
    }

    /**
     * @return string[][]
     */
    public function searchVersionDataProvider(): array
    {
        return [
            ['authorization-11', 'authorization-1'],
            ['authentication-11', 'authentication-1'],
            ['1-1', '11'],
            ['1-2', '12'],
            ['1-3', '13'],
            ['3-0', '30'],
            ['4-0', '40'],
            ['2-10', '20'],
            ['2-2', '20'],
            ['other', 'other'],
        ];
    }

    /**
     * @dataProvider searchVersionDataProvider
     * @param string $version The request version.
     * @param string $expected The expected search version.
     * @return void
     */
    public function testSearchVersionTranslation(string $version, string $expected): void
    {
        $index = $this
            ->getMockBuilder(SearchIndex::class)
            ->onlyMethods(['search'])
            ->getMock();

        $index
            ->expects($this->once())
            ->method('search')
            ->with('en', $expected)
            ->willReturn([]);

        IndexRegistry::set('Search', $index);

        $this->get("/search?lang=en&version={$version}&q=query");
    }
}
