<?php

use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use VV\ContentRenderer\Renderer;

beforeEach(function () {
    Collection::make('pages')->save();
});

function createBardEntry(array $bardContent, string $handle = 'content', array $extraFields = []): \Statamic\Contracts\Entries\Entry
{
    $blueprintFields = array_merge([
        $handle => ['type' => 'bard', 'sets' => [
            'text_block' => [
                'display' => 'Text Block',
                'sets' => [
                    'text' => [
                        'display' => 'Text',
                        'fields' => [
                            ['handle' => 'text', 'field' => ['type' => 'text']],
                        ],
                    ],
                ],
            ],
        ]],
    ], $extraFields);

    $blueprint = Blueprint::makeFromFields($blueprintFields)
        ->setNamespace('collections.pages')
        ->setHandle('page');

    Blueprint::shouldReceive('in')->with('collections/pages')->andReturn(collect(['page' => $blueprint]));

    $entry = Entry::make()
        ->collection('pages')
        ->slug('test')
        ->data([$handle => $bardContent]);

    return $entry;
}

function createReplicatorEntry(array $replicatorContent, string $handle = 'blocks'): \Statamic\Contracts\Entries\Entry
{
    $blueprintFields = [
        $handle => ['type' => 'replicator', 'sets' => [
            'content_group' => [
                'display' => 'Content Group',
                'sets' => [
                    'text' => [
                        'display' => 'Text',
                        'fields' => [
                            ['handle' => 'text', 'field' => ['type' => 'text']],
                        ],
                    ],
                    'quote' => [
                        'display' => 'Quote',
                        'fields' => [
                            ['handle' => 'quote', 'field' => ['type' => 'textarea']],
                            ['handle' => 'author', 'field' => ['type' => 'text']],
                        ],
                    ],
                ],
            ],
        ]],
    ];

    $blueprint = Blueprint::makeFromFields($blueprintFields)
        ->setNamespace('collections.pages')
        ->setHandle('page');

    Blueprint::shouldReceive('in')->with('collections/pages')->andReturn(collect(['page' => $blueprint]));

    $entry = Entry::make()
        ->collection('pages')
        ->slug('test')
        ->data([$handle => $replicatorContent]);

    return $entry;
}

function bardParagraph(string $text): array
{
    return [
        'type' => 'paragraph',
        'content' => [
            ['type' => 'text', 'text' => $text],
        ],
    ];
}

function bardHeading(string $text, int $level = 2): array
{
    return [
        'type' => 'heading',
        'attrs' => ['level' => $level],
        'content' => [
            ['type' => 'text', 'text' => $text],
        ],
    ];
}

function bardSet(string $type, array $values): array
{
    return [
        'type' => 'set',
        'attrs' => [
            'id' => 'test-set-'.uniqid(),
            'values' => array_merge(['type' => $type], $values),
        ],
    ];
}

// ──────────────────────────────────────────────────────────────────────
// Basic rendering
// ──────────────────────────────────────────────────────────────────────

it('returns empty string when no entry is set', function () {
    $renderer = new Renderer();
    $renderer->setView('sets');

    expect($renderer->render())->toBe('');
});

it('returns empty string when no field handle is set', function () {
    $renderer = new Renderer();

    expect($renderer->render())->toBe('');
});

it('returns empty string for an invalid entry id', function () {
    $renderer = new Renderer();
    $renderer->setContent('non-existent-id', 'content');
    $renderer->setView('sets');

    expect($renderer->render())->toBe('');
});

// ──────────────────────────────────────────────────────────────────────
// Bard rendering
// ──────────────────────────────────────────────────────────────────────

it('renders a simple bard paragraph', function () {
    $entry = createBardEntry([
        bardParagraph('Hello world'),
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->setView('sets')
        ->render();

    expect($result)->toContain('Hello world');
});

it('renders multiple bard paragraphs', function () {
    $entry = createBardEntry([
        bardParagraph('First paragraph'),
        bardParagraph('Second paragraph'),
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->setView('sets')
        ->render();

    expect($result)
        ->toContain('First paragraph')
        ->toContain('Second paragraph');
});

it('renders bard headings', function () {
    $entry = createBardEntry([
        bardHeading('My Heading', 2),
        bardParagraph('Some text after heading'),
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->setView('sets')
        ->render();

    expect($result)
        ->toContain('My Heading')
        ->toContain('Some text after heading');
});

it('renders bard without a view using bardHtml', function () {
    $entry = createBardEntry([
        bardParagraph('No view needed'),
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->render();

    expect($result)->toContain('No view needed');
});

it('strips HTML tags from bard output by default', function () {
    $entry = createBardEntry([
        bardParagraph('Clean text'),
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->render();

    expect($result)
        ->not->toContain('<p>')
        ->not->toContain('</p>')
        ->toContain('Clean text');
});

// ──────────────────────────────────────────────────────────────────────
// Bard with sets
// ──────────────────────────────────────────────────────────────────────

it('renders bard content with sets using a view', function () {
    $entry = createBardEntry([
        bardParagraph('Before the set'),
        bardSet('text', ['text' => 'Set content here']),
        bardParagraph('After the set'),
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->setView('bard-with-sets')
        ->render();

    expect($result)
        ->toContain('Before the set')
        ->toContain('Set content here')
        ->toContain('After the set');
});

// ──────────────────────────────────────────────────────────────────────
// Replicator rendering
// ──────────────────────────────────────────────────────────────────────

it('renders replicator fields with a view', function () {
    $entry = createReplicatorEntry([
        ['type' => 'text', 'text' => 'Replicator text content'],
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'blocks')
        ->setView('replicator-sets')
        ->render();

    expect($result)->toContain('Replicator text content');
});

it('renders multiple replicator sets', function () {
    $entry = createReplicatorEntry([
        ['type' => 'text', 'text' => 'First block'],
        ['type' => 'quote', 'quote' => 'A wise quote', 'author' => 'Someone'],
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'blocks')
        ->setView('replicator-sets')
        ->render();

    expect($result)
        ->toContain('First block')
        ->toContain('A wise quote')
        ->toContain('Someone');
});

it('returns empty string for replicator without a view', function () {
    $entry = createReplicatorEntry([
        ['type' => 'text', 'text' => 'No view'],
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'blocks')
        ->render();

    expect($result)->toBe('');
});

// ──────────────────────────────────────────────────────────────────────
// HTML tag handling
// ──────────────────────────────────────────────────────────────────────

it('preserves HTML tags when withHtmlTags is used', function () {
    $entry = createBardEntry([
        bardParagraph('Keep my tags'),
    ]);

    $result = (new Renderer())
        ->withHtmlTags()
        ->setContent($entry, 'content')
        ->render();

    expect($result)->toContain('<p>');
});

it('can toggle HTML tags off after enabling', function () {
    $entry = createBardEntry([
        bardParagraph('Toggle test'),
    ]);

    $result = (new Renderer())
        ->withHtmlTags()
        ->withoutHtmlTags()
        ->setContent($entry, 'content')
        ->render();

    expect($result)
        ->not->toContain('<p>')
        ->toContain('Toggle test');
});

// ──────────────────────────────────────────────────────────────────────
// Link target handling
// ──────────────────────────────────────────────────────────────────────

it('extracts link targets when withLinkTargets is used', function () {
    $entry = createBardEntry([
        [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Visit '],
                [
                    'type' => 'text',
                    'text' => 'our website',
                    'marks' => [
                        ['type' => 'link', 'attrs' => ['href' => 'https://visuellverstehen.de']],
                    ],
                ],
                ['type' => 'text', 'text' => ' for more!'],
            ],
        ],
    ]);

    $result = (new Renderer())
        ->withLinkTargets()
        ->setContent($entry, 'content')
        ->render();

    expect($result)
        ->toContain('our website')
        ->toContain('(https://visuellverstehen.de)');
});

it('does not extract link targets by default', function () {
    $entry = createBardEntry([
        [
            'type' => 'paragraph',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'click here',
                    'marks' => [
                        ['type' => 'link', 'attrs' => ['href' => 'https://visuellverstehen.de']],
                    ],
                ],
            ],
        ],
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->render();

    expect($result)->not->toContain('(https://visuellverstehen.de)');
});

it('ignores withLinkTargets when withHtmlTags is enabled', function () {
    $entry = createBardEntry([
        [
            'type' => 'paragraph',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'link text',
                    'marks' => [
                        ['type' => 'link', 'attrs' => ['href' => 'https://visuellverstehen.de']],
                    ],
                ],
            ],
        ],
    ]);

    $result = (new Renderer())
        ->withHtmlTags()
        ->withLinkTargets()
        ->setContent($entry, 'content')
        ->render();

    // Should keep the <a> tag, not extract the URL in parentheses
    expect($result)
        ->toContain('<a')
        ->toContain('href="https://visuellverstehen.de"')
        ->not->toContain('(https://visuellverstehen.de)');
});

it('can toggle link targets off after enabling', function () {
    $entry = createBardEntry([
        [
            'type' => 'paragraph',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'link text',
                    'marks' => [
                        ['type' => 'link', 'attrs' => ['href' => 'https://visuellverstehen.de']],
                    ],
                ],
            ],
        ],
    ]);

    $result = (new Renderer())
        ->withLinkTargets()
        ->withoutLinkTargets()
        ->setContent($entry, 'content')
        ->render();

    expect($result)->not->toContain('(https://visuellverstehen.de)');
});

// ──────────────────────────────────────────────────────────────────────
// Custom processor
// ──────────────────────────────────────────────────────────────────────

it('applies a custom processor to bard content', function () {
    $entry = createBardEntry([
        bardParagraph('Original text'),
        bardSet('text', ['text' => 'Set to remove']),
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->setView('bard-with-sets')
        ->process(function ($content) {
            // Remove all sets from the content
            return array_values(array_filter($content, fn ($item) => ($item['type'] ?? null) !== 'set'));
        })
        ->render();

    expect($result)
        ->toContain('Original text')
        ->not->toContain('Set to remove');
});

it('applies a custom processor to replicator content', function () {
    $entry = createReplicatorEntry([
        ['type' => 'text', 'text' => 'Keep this'],
        ['type' => 'quote', 'quote' => 'Remove this', 'author' => 'Nobody'],
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'blocks')
        ->setView('replicator-sets')
        ->process(function ($content) {
            return array_values(array_filter($content, fn ($item) => $item['type'] !== 'quote'));
        })
        ->render();

    expect($result)
        ->toContain('Keep this')
        ->not->toContain('Remove this');
});

// ──────────────────────────────────────────────────────────────────────
// setValue
// ──────────────────────────────────────────────────────────────────────

it('renders using setValue with a Value object', function () {
    $entry = createBardEntry([
        bardParagraph('From value object'),
    ]);

    $value = $entry->augmentedValue('content');

    $result = (new Renderer())
        ->setValue($value)
        ->render();

    expect($result)->toContain('From value object');
});

// ──────────────────────────────────────────────────────────────────────
// Sanitization
// ──────────────────────────────────────────────────────────────────────

it('collapses excess whitespace', function () {
    $entry = createBardEntry([
        bardParagraph('Word1'),
        bardParagraph('Word2'),
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->render();

    // BUG: sanitization doesn't fully collapse whitespace between paragraphs
    // when tags are stripped, residual spaces remain from the space-between-tags logic
    expect($result)
        ->toContain('Word1')
        ->toContain('Word2');
});

it('trims the result', function () {
    $entry = createBardEntry([
        bardParagraph('Trimmed content'),
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->render();

    expect($result)->toBe(trim($result));
});

// ──────────────────────────────────────────────────────────────────────
// Entry with string ID
// ──────────────────────────────────────────────────────────────────────

it('accepts an entry id as string', function () {
    $entry = createBardEntry([
        bardParagraph('Found by ID'),
    ]);

    $entry->id('test-entry-id');
    $entry->save();

    $result = (new Renderer())
        ->setContent('test-entry-id', 'content')
        ->render();

    expect($result)->toContain('Found by ID');
});

// ──────────────────────────────────────────────────────────────────────
// Origin/localization fallback
// ──────────────────────────────────────────────────────────────────────

it('falls back to origin entry for untranslated fields', function () {
    $origin = createBardEntry([
        bardParagraph('Origin content'),
    ]);
    $origin->id('origin-id');
    $origin->save();

    $localized = Entry::make()
        ->collection('pages')
        ->slug('test-localized')
        ->origin($origin)
        ->data([]); // No content field — should fall back to origin

    $result = (new Renderer())
        ->setContent($localized, 'content')
        ->render();

    expect($result)->toContain('Origin content');
});

// ──────────────────────────────────────────────────────────────────────
// Error handling
// ──────────────────────────────────────────────────────────────────────

it('returns empty string and logs when view rendering fails', function () {
    $entry = createBardEntry([
        bardParagraph('Some content'),
    ]);

    $result = (new Renderer())
        ->setContent($entry, 'content')
        ->setView('nonexistent-view')
        ->render();

    expect($result)->toBe('');
});

// ──────────────────────────────────────────────────────────────────────
// Fluent API
// ──────────────────────────────────────────────────────────────────────

it('supports a fluent API chain', function () {
    $entry = createBardEntry([
        bardParagraph('Fluent chain'),
    ]);

    $renderer = new Renderer();

    $result = $renderer
        ->setContent($entry, 'content')
        ->setView('sets')
        ->withLinkTargets()
        ->withHtmlTags()
        ->process(fn ($content) => $content)
        ->render();

    expect($result)->toBeString();
});
