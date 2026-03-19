# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-03-16

### Added
- Support for Statamic v6

### Changed
- **BREAKING**: Bumped minimum PHP version from 8.1 to 8.2
- **BREAKING**: The `process()` callback now receives raw field data instead of CP-processed data
- **BREAKING**: Dropped support for Statamic v3, v4, and v5

### Removed
- **BREAKING**: Removed internal `preProcess()`/`process()` round-trip. The Renderer now uses the public fieldtype `augment()` API directly.

### Fixed
- **BREAKING**: `renderWithoutView()` now uses the fieldtype's own Bard configuration instead of creating an unconfigured Bard instance via `CoreModifiers::bardHtml()`. This ensures custom extensions, set detection, and field config are properly applied.

### Migration Guide

#### For users of the `process()` callback

The callback now receives raw data directly from the entry instead of data that has been through Statamic's CP form processing pipeline.

**Before (v1.x):**
```php
$renderer->process(function ($content) {
    // $content was pre-processed by Statamic's CP pipeline
    // Sets had their inner values processed through their fieldtypes
    return array_filter($content, fn ($item) => ($item['type'] ?? null) !== 'set');
});
```

**After (v2.x):**
```php
$renderer->process(function ($content) {
    // $content is now raw data from the entry
    // Structure is the same for top-level items
    return array_filter($content, fn ($item) => ($item['type'] ?? null) !== 'set');
});
```

**Note:** If you were modifying inner field values within sets, those values will now be raw instead of processed. Most users filtering by `type` key will not need changes.

#### Requirements

- PHP 8.2+
- Statamic 6.0+

## [1.1.0] - 2025-03-16

### Added
- Test infrastructure with 46 Pest tests
- Support for `setValue()` to pass Value objects directly

### Fixed
- Sanitization: Fixed newline merging issues
- Sanitization: Fixed residual whitespace after tag stripping
- Sanitization: Fixed fullstop splitting (e.g., "word.Another" → "word. Another")
- Error handling: `renderWithView()` now returns empty string and logs instead of returning exception messages

### Changed
- Added typed properties throughout
- Code cleanup and internal refactoring

### Documentation
- Fixed README typo
- Added `setValue()` documentation
- Removed outdated keywords from composer.json

## [1.0.0] - Initial Release

- Initial release with support for Bard and Replicator field rendering
- View-based rendering with set support
- HTML tag stripping and sanitization
- Link target extraction
- Custom processor callback
