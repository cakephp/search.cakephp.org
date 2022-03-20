<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\ElasticSearch\IndexRegistry;
use Cake\ElasticSearch\TestSuite\TestFixture;

class SearchFixture extends TestFixture
{
    public $table = 'cake-docs-test-en';

    public $connection = 'test_elastic';

    /**
     * The mapping data.
     *
     * @var mixed[]
     */
    public $schema = [
        'type' => ['type' => 'keyword', 'index' => false],
        'priority' => ['type' => 'keyword'],
        'url' => ['type' => 'keyword', 'index' => false],
        'page_url' => ['type' => 'keyword'],
        'level' => ['type' => 'short'],
        'max_level' => ['type' => 'short'],
        'position' => ['type' => 'short'],
        'max_position' => ['type' => 'short'],
        'hierarchy' => ['type' => 'text'],
        'title' => ['type' => 'text'],
        'contents' => ['type' => 'text'],
    ];

    /**
     * The fixture records
     *
     * @var mixed[]
     */
    public $records = [
        [
            'id' => 'test-html-test',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/test.html#test',
            'page_url' => '/test/1/test.html',
            'level' => 0,
            'max_level' => 2,
            'position' => 0,
            'max_position' => 3,
            'hierarchy' => [
                'Test',
            ],
            'title' => 'Test',
            'contents' => 'class Foo\\Bar\\Baz Test root section content.',
        ],
        [
            'id' => 'test-html-level-1-subsection-1-title',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/test.html#level-1-subsection-1-title',
            'page_url' => '/test/1/test.html',
            'level' => 1,
            'max_level' => 2,
            'position' => 1,
            'max_position' => 3,
            'hierarchy' => [
                'Test',
                'Level 1 Subsection 1 <b>Title</b>',
            ],
            'title' => 'Level 1 Subsection 1 <b>Title</b>',
            'contents' => 'Foo\\Bar\\Baz::method(string $argument) $argument - Method argument description. ' .
                'Level 1 subsection 1 <b>content</b>: $foo = new \\Foo\\Bar\\Baz(); $value = $foo->method(\'argument\'); ' .
                'Lorem ipsum, method(\'argument\') dolor sit amet: <method value="argument" /> Admonition content',
        ],
        [
            'id' => 'test-html-level-2-subsection-1-title',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/test.html#level-2-subsection-1-title',
            'page_url' => '/test/1/test.html',
            'level' => 2,
            'max_level' => 2,
            'position' => 2,
            'max_position' => 3,
            'hierarchy' => [
                'Test',
                'Level 1 Subsection 1 Title',
                'Level 2 Subsection 1 Title',
            ],
            'title' => 'Level 2 Subsection 1 Title',
            'contents' => 'Level 2 subsection 1 content.',
        ],
        [
            'id' => 'test-html-level-1-subsection-2-title',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/test.html#level-1-subsection-2-title',
            'page_url' => '/test/1/test.html',
            'level' => 1,
            'max_level' => 2,
            'position' => 3,
            'max_position' => 3,
            'hierarchy' => [
                'Test',
                'Level 1 Subsection 2 Title',
            ],
            'title' => 'Level 1 Subsection 2 Title',
            'contents' => 'Level 1 subsection 2 content.',
        ],
        [
            'id' => 'test-nested-html-nested',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/test/nested.html#nested',
            'page_url' => '/test/1/test/nested.html',
            'level' => 0,
            'max_level' => 1,
            'position' => 0,
            'max_position' => 1,
            'hierarchy' => [
                'Test',
                'Nested',
            ],
            'title' => 'Nested',
            'contents' => 'Nested root section content.',
        ],
        [
            'id' => 'test-nested-html-level-1-subsection-1-title',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/test/nested.html#level-1-subsection-1-title',
            'page_url' => '/test/1/test/nested.html',
            'level' => 1,
            'max_level' => 1,
            'position' => 1,
            'max_position' => 1,
            'hierarchy' => [
                'Test',
                'Nested',
                'Level 1 Subsection 1 Title',
            ],
            'title' => 'Level 1 Subsection 1 Title',
            'contents' => 'Level 1 subsection 1 content.',
        ],
        [
            'id' => 'more-html-more',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/more.html#more',
            'page_url' => '/test/1/more.html',
            'level' => 0,
            'max_level' => 0,
            'position' => 0,
            'max_position' => 0,
            'hierarchy' => [
                'More',
            ],
            'title' => 'More',
            'contents' => 'More root section content.',
        ],
        [
            'id' => 'appendices-html-appendices',
            'type' => 'internal',
            'priority' => 'low',
            'url' => '/test/1/appendices.html#appendices',
            'page_url' => '/test/1/appendices.html',
            'level' => 0,
            'max_level' => 0,
            'position' => 0,
            'max_position' => 0,
            'hierarchy' => [
                'Appendices',
            ],
            'title' => 'Appendices',
            'contents' => 'Appendices root section content.',
        ],
        [
            'id' => 'appendices-low-priority-html-low-priority',
            'type' => 'internal',
            'priority' => 'low',
            'url' => '/test/1/appendices/low-priority.html#low-priority',
            'page_url' => '/test/1/appendices/low-priority.html',
            'level' => 0,
            'max_level' => 0,
            'position' => 0,
            'max_position' => 0,
            'hierarchy' => [
                'Appendices',
                'Low Priority',
            ],
            'title' => 'Low Priority',
            'contents' => 'Low priority root section content.',
        ],
        [
            'id' => 'https-example-com-foo',
            'type' => 'external',
            'priority' => 'normal',
            'url' => 'https://example.com/foo',
            'page_url' => 'https://example.com/foo',
            'level' => 0,
            'max_level' => 0,
            'position' => 0,
            'max_position' => 0,
            'hierarchy' => [
                'Foo',
            ],
            'title' => 'Foo',
            'contents' => null,
        ],
        [
            'id' => 'https-example-com-bar',
            'type' => 'external',
            'priority' => 'normal',
            'url' => 'https://example.com/bar',
            'page_url' => 'https://example.com/bar',
            'level' => 0,
            'max_level' => 0,
            'position' => 0,
            'max_position' => 0,
            'hierarchy' => [
                'Bar',
            ],
            'title' => 'Bar',
            'contents' => null,
        ],
        [
            'id' => 'https-example-com-baz',
            'type' => 'external',
            'priority' => 'normal',
            'url' => 'https://example.com/baz',
            'page_url' => 'https://example.com/baz',
            'level' => 0,
            'max_level' => 0,
            'position' => 0,
            'max_position' => 0,
            'hierarchy' => [
                'Baz',
            ],
            'title' => 'Baz',
            'contents' => null,
        ],
    ];

    public function getIndex()
    {
        return IndexRegistry::get('Search')->setName('cake-docs-test-en');
    }
}
