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
    expect(sanitize($input))->toBe('line one line two');
});

it('removes newlines', function () {
    $input = "hello\nworld";
    expect(sanitize($input))->toBe('hello world');
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
    expect(sanitize('end.Start of next'))->toBe('end. Start of next');
});

it('separates fullstops at end of string', function () {
    expect(sanitize('end.Start'))->toBe('end. Start');
});

it('does not split numbers with fullstops', function () {
    expect(sanitize('version 1.5 is out'))->toBe('version 1.5 is out');
});

it('does not split URLs', function () {
    expect(sanitize('visit example.com today'))->toBe('visit example.com today');
});

it('does not split lowercase.lowercase', function () {
    expect(sanitize('e.g. this'))->toBe('e.g. this');
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
    expect(sanitize('<div><span></span></div>'))->toBe('');
});
