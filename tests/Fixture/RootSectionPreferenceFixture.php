<?php
declare(strict_types=1);

namespace App\Test\Fixture;

class RootSectionPreferenceFixture extends SearchFixture
{
    /**
     * The fixture records
     *
     * @var mixed[]
     */
    public $records = [
        [
            'id' => 'term',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/term.html#term',
            'page_url' => '/test/1/term.html',
            'level' => 0,
            'max_level' => 2,
            'position' => 0,
            'max_position' => 2,
            'hierarchy' => [
                'Term',
            ],
            'title' => 'Term',
            'contents' => 'Term root section content.',
        ],
        [
            'id' => 'nested-term',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/term.html#nested-term',
            'page_url' => '/test/1/term.html',
            'level' => 1,
            'max_level' => 2,
            'position' => 1,
            'max_position' => 2,
            'hierarchy' => [
                'Term',
                'Nested Term',
            ],
            'title' => 'Nested Term',
            'contents' => 'Nested term section content.',
        ],
        [
            'id' => 'deeper-nested-term',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/term.html#deeper-nested-term',
            'page_url' => '/test/1/term.html',
            'level' => 2,
            'max_level' => 2,
            'position' => 2,
            'max_position' => 2,
            'hierarchy' => [
                'Term',
                'Nested Term',
                'More Term Repetitions For Deeper Nested Term',
            ],
            'title' => 'More Term Repetitions For Deeper Nested Term',
            'contents' => 'Deeper nested term section content.',
        ],
    ];
}
