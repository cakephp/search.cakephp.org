<?php
declare(strict_types=1);

namespace App\Test\Fixture;

class LowPriorityDeboostingFixture extends SearchFixture
{
    /**
     * The fixture records
     *
     * @var mixed[]
     */
    public $records = [
        [
            'id' => 'term-low-priority',
            'type' => 'internal',
            'priority' => 'low',
            'url' => '/test/1/term-low-priority.html#term',
            'page_url' => '/test/1/term-low-priority.html',
            'level' => 0,
            'max_level' => 0,
            'position' => 0,
            'max_position' => 0,
            'hierarchy' => [
                'Term',
            ],
            'title' => 'Term',
            'contents' => 'Term root section content.',
        ],
        [
            'id' => 'term-low-quality',
            'type' => 'internal',
            'priority' => 'normal',
            'url' => '/test/1/term-low-quality.html#low-quality',
            'page_url' => '/test/1/term-low-quality.html',
            'level' => 0,
            'max_level' => 0,
            'position' => 0,
            'max_position' => 0,
            'hierarchy' => [
                'Low Quality',
            ],
            'title' => 'Low Quality',
            'contents' => 'Low quality root section content mentioning the term.',
        ],
    ];
}
