<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Index;

use App\Model\Index\SearchIndex;
use App\QueryTranslation\QueryString;
use Cake\ElasticSearch\IndexRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

class SearchIndexTest extends TestCase
{
    public $autoFixtures = false;

    protected $fixtures = [
        'app.RootSectionPreference',
        'app.HigherSectionPositionPreference',
        'app.LowPriorityDeboosting',
        'app.Search',
    ];

    /**
     * @var \App\Model\Index\SearchIndex
     */
    protected $index;

    public function setUp(): void
    {
        parent::setUp();
        IndexRegistry::clear();

        $index = IndexRegistry::get('Search')->setName('cake-docs-test-en');
        assert($index instanceof SearchIndex);
        $this->index = $index;
    }

    public function tearDown(): void
    {
        parent::tearDown();
        IndexRegistry::clear();
    }

    /**
     * Tests that root sections will score better than subsections that
     * do have more matches because of repeated occurrences of the
     * searched term.
     *
     * @return void
     */
    public function testRootSectionsArePreferred(): void
    {
        $this->loadFixtures('RootSectionPreference');

        $results = $this->index->search('en', 'test', new QueryString('term'));
        $this->assertSame('/test/1/term.html#term', $results['data'][0]['url']);
    }

    /**
     * Tests that higher positioned sections will score better than lower
     * positioned ones.
     *
     * @return void
     */
    public function testHigherPositionedSectionsArePreferred(): void
    {
        $this->loadFixtures('HigherSectionPositionPreference');

        $results = $this->index->search('en', 'test', new QueryString('term'));
        $this->assertSame('/test/1/term.html#term-position-0', $results['data'][0]['url']);
    }

    /**
     * Tests that low priority content is deboosted, so that even low
     * quality content will score better.
     *
     * @return void
     */
    public function testLowPriorityIsDeboosted(): void
    {
        $this->loadFixtures('LowPriorityDeboosting');

        $results = $this->index->search('en', 'test', new QueryString('term'));
        $this->assertSame(
            [
                '/test/1/term-low-quality.html#low-quality',
                '/test/1/term-low-priority.html#term',
            ],
            array_column($results['data'], 'url')
        );
    }

    /**
     * Tests that partial terms do find matches, and that they are
     * being highlighted properly.
     *
     * @return void
     */
    public function testPartialMatches(): void
    {
        $this->loadFixtures('Search');

        $results = $this->index->search('en', 'test', new QueryString('subse'), [
            'highlightPreTag' => '{{',
            'highlightPostTag' => '}}',
        ]);

        $this->assertSame(
            '/test/1/test.html#level-1-subsection-1-title',
            Hash::get($results['data'], '0.url')
        );
        $this->assertSame(
            'Level 1 {{subsection}} 1 <b>content</b>: $foo = new \Foo\Bar\Baz(',
            Hash::get($results['data'], '0.highlights.contents.0')
        );
        $this->assertSame(
            'Level 1 {{Subsection}} 1 <b>Title</b>',
            Hash::get($results['data'], '0.highlights.hierarchy.1')
        );

        $this->assertSame(
            '/test/1/test/nested.html#level-1-subsection-1-title',
            Hash::get($results['data'], '1.url')
        );
        $this->assertSame(
            'Level 1 {{subsection}} 1 content.',
            Hash::get($results['data'], '1.highlights.contents.0')
        );
        $this->assertSame(
            'Level 1 {{Subsection}} 1 Title',
            Hash::get($results['data'], '1.highlights.hierarchy.2')
        );
    }

    /**
     * Tests that terms with typos do find matches, and that they are
     * being highlighted properly.
     *
     * @return void
     */
    public function testTypos(): void
    {
        $this->loadFixtures('Search');

        $results = $this->index->search('en', 'test', new QueryString('tset'), [
            'highlightPreTag' => '{{',
            'highlightPostTag' => '}}',
        ]);

        $this->assertSame('/test/1/test.html#test', Hash::get($results['data'], '0.url'));
        $this->assertSame('{{Test}}', Hash::get($results['data'], '0.highlights.hierarchy.0'));

        $this->assertSame('/test/1/test/nested.html#nested', Hash::get($results['data'], '1.url'));
        $this->assertSame('{{Test}}', Hash::get($results['data'], '1.highlights.hierarchy.0'));
    }

    public function testMaxPage(): void
    {
        $this->loadFixtures('Search');

        $results = $this->index->search('en', 'test', new QueryString('subsection'), [
            'page' => 2000,
        ]);

        $this->assertSame(1000, $results['page']);
        $this->assertEmpty($results['data']);
    }

    public function testMaxLimit(): void
    {
        $this->loadFixtures('Search');

        $entities = [];
        for ($i = 0; $i < 100; $i++) {
            $entities[] = $this->index->newEntity([
                'id' => "Limit-$i",
                'type' => 'internal',
                'priority' => 'normal',
                'url' => "/test/1/test-$i.html#limit-$i",
                'page_url' => "/test/1/test-$i.html",
                'level' => 0,
                'max_level' => 0,
                'position' => $i,
                'max_position' => 100,
                'hierarchy' => [
                    "Title $i",
                ],
                'title' => "Title $i",
                'contents' => "Contents $i",
            ]);
        }
        $this->index->saveMany($entities, ['refresh' => true]);

        $this->assertGreaterThan(100, $this->index->find()->count());

        $results = $this->index->search('en', 'test', new QueryString('-non_existent_term_to_find_everything'), [
            'limit' => 2000,
        ]);

        $this->assertGreaterThan(100, $results['total']);
        $this->assertCount(100, $results['data']);
    }

    public function testEmptyResult(): void
    {
        $this->loadFixtures('Search');

        $results = $this->index->search('en', 'test', new QueryString('non_existent_term'));
        $this->assertSame(
            [
                'page' => 1,
                'total' => 0,
                'data' => [],
                'terms' => ['non_existent_term'],
            ],
            $results
        );
    }

    public function testCollapsing(): void
    {
        $this->loadFixtures('Search');

        $results = $this->index->search('en', 'test', new QueryString('-non_existent_term_to_find_everything'), [
            'limit' => 100,
        ]);

        $this->assertGreaterThan(8, $this->index->find()->count());
        $this->assertSame(8, $results['total']);
        $this->assertCount(8, array_unique(array_column($results['data'], 'page_url')));
    }
}
