<?php
declare(strict_types=1);

namespace App\Test\Fixture;

class HigherSectionPositionPreferenceFixture extends SearchFixture
{
    /**
     * The fixture records
     *
     * @var mixed[]
     */
    public $records = [
        [
            'id' => 'term-position-0',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/term.html#term-position-0',
            'page_url' => '/test/1/term.html',
            'level' => 0,
            'max_level' => 0,
            'position' => 0,
            'max_position' => 2,
            'hierarchy' => [
                'Term',
            ],
            'title' => 'Term Position 0',
            'contents' => 'Term section position 0 content.',
        ],
        [
            'id' => 'term-position-1',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/term.html#term-position-1',
            'page_url' => '/test/1/term.html',
            'level' => 0,
            'max_level' => 0,
            'position' => 1,
            'max_position' => 2,
            'hierarchy' => [
                'Term',
            ],
            'title' => 'Term Term Position 1',
            'contents' => 'Term section position 1 content.',
        ],
        [
            'id' => 'term-position-2',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/term.html#term-position-2',
            'page_url' => '/test/1/term.html',
            'level' => 0,
            'max_level' => 0,
            'position' => 2,
            'max_position' => 2,
            'hierarchy' => [
                'Term',
            ],
            'title' => 'Term Term Term Position 2',
            'contents' => 'Term section position 2 content.',
        ],
    ];
}
