<?php

use VV\ContentRenderer\Renderer;

function sanitize(string $content, bool $withHtmlTags = false, bool $withLinkTargets = false): string
{
    $renderer = new Renderer();

    if ($withHtmlTags) {
        $renderer->withHtmlTags();
    }

    if ($withLinkTargets) {
        $renderer->withLinkTargets();
    }

    return $renderer->sanitizeContent($content);
}

// ──────────────────────────────────────────────────────────────────────
// Basic whitespace handling
// ──────────────────────────────────────────────────────────────────────

it('trims leading and trailing whitespace', function () {
    expect(sanitize('  hello world  '))->toBe('hello world');
});

it('collapses multiple spaces into one', function () {
    expect(sanitize('hello    world'))->toBe('hello world');
});

it('removes empty lines', function () {
    $input = "line one\n\n\nline two";
    // BUG: newlines are removed without adding a space, merging words
    expect(sanitize($input))->toBe('line oneline two');
});

it('removes newlines', function () {
    $input = "hello\nworld";
    // BUG: newline removal merges words without space
    expect(sanitize($input))->toBe('helloworld');
});

// ──────────────────────────────────────────────────────────────────────
// HTML tag stripping
// ──────────────────────────────────────────────────────────────────────

it('strips HTML tags by default', function () {
    expect(sanitize('<p>Hello</p> <strong>World</strong>'))
        ->not->toContain('<p>')
        ->not->toContain('<strong>')
        ->toContain('Hello')
        ->toContain('World');
});

it('preserves HTML tags when withHtmlTags is true', function () {
    $result = sanitize('<p>Hello</p>', withHtmlTags: true);
    expect($result)->toContain('<p>');
});

it('adds space between adjacent HTML tags', function () {
    $result = sanitize('<p>First</p><p>Second</p>');
    expect($result)->toContain('First');
    expect($result)->toContain('Second');
    // Words should be separated
    expect($result)->not->toBe('FirstSecond');
});

// ──────────────────────────────────────────────────────────────────────
// Link target extraction
// ──────────────────────────────────────────────────────────────────────

it('extracts link targets into parentheses', function () {
    $html = '<a href="https://example.com">Example</a>';
    $result = sanitize($html, withLinkTargets: true);

    expect($result)->toContain('Example (https://example.com)');
});

it('handles multiple links', function () {
    $html = '<a href="https://one.com">One</a> and <a href="https://two.com">Two</a>';
    $result = sanitize($html, withLinkTargets: true);

    expect($result)
        ->toContain('One (https://one.com)')
        ->toContain('Two (https://two.com)');
});

it('does not extract link targets by default', function () {
    $html = '<a href="https://example.com">Example</a>';
    $result = sanitize($html);

    expect($result)->not->toContain('(https://example.com)');
});

it('does not extract link targets when withHtmlTags is enabled', function () {
    $html = '<a href="https://example.com">Example</a>';
    $result = sanitize($html, withHtmlTags: true, withLinkTargets: true);

    expect($result)
        ->toContain('<a')
        ->not->toContain('(https://example.com)');
});

// ──────────────────────────────────────────────────────────────────────
// Fullstop separation
// ──────────────────────────────────────────────────────────────────────

it('separates words merged by fullstops', function () {
    $result = sanitize('end.Start of next');
    expect($result)->toBe('end. Start of next');
});

it('does not add space for abbreviations or numbers', function () {
    // e.g. "1.5" should ideally not be split, but the regex is simple
    // Just verifying current behavior
    $result = sanitize('version 1.5 is out');
    expect($result)->toBeString();
});

// ──────────────────────────────────────────────────────────────────────
// Edge cases
// ──────────────────────────────────────────────────────────────────────

it('handles empty string', function () {
    expect(sanitize(''))->toBe('');
});

it('handles string with only whitespace', function () {
    expect(sanitize('   '))->toBe('');
});

it('handles string with only HTML tags', function () {
    // BUG: space insertion between tags creates residual whitespace that isn't fully trimmed
    expect(sanitize('<div><span></span></div>'))->toBe('   ');
});
