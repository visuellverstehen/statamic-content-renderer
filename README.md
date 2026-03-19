# Content Renderer

A Statamic addon that renders content from bard and replicator fields to make their content searchable with [search transformers](https://statamic.dev/search#transforming-fields).

## Requirements

| Version | Statamic | PHP |
|---------|----------|-----|
| 2.x | ^6.0 | ^8.2 |
| 1.x | ^3.4 \|\| ^4.0 \|\| ^5.0 | ^8.1 |

## How to Install

Run the following command from your project root:

``` bash
composer require visuellverstehen/statamic-content-renderer
```

## How to Use

The content renderer is available through the `Renderer()` class. To render the content of a replicator or bard field, the class requires a view that provides the info of how to display all the configured sets, e. g.:

```antlers
{{# resources/views/sets.antlers.php #}}

{{ my_replicator_field }}
    {{ partial src="sets/{type}" }}
{{ /my_replicator_field }}
```

This view needs to be passed on to the renderer class.

```php
use VV\ContentRenderer\Renderer;

// ...

$renderer = new Renderer();
$renderer->setContent($entry, 'my_replicator_field');
$renderer->setView('sets');

$content = $renderer->render();
```

The renderer uses the view to render all sets (and all written content of bard fields), sanitizes the content (strips all HTML tags etc.) and returns a string containing every written word from within the field.

This can be used within a [search transformer](https://statamic.dev/search#transforming-fields) to make the content of bard and replicator fields available for full-text search:

```php
namespace App\SearchTransformers;

use VV\ContentRenderer\Renderer;
 
class MyReplicatorFieldTransformer
{
    public function handle($value, $field, $searchable)
    {
        $renderer = new Renderer();
        $renderer->setContent($searchable, 'my_replicator_field');
        $renderer->setView('sets');
        
        return $renderer->render();
    }
}
```

### Preserving links targets

When using the Content Renderer within a search transformer, it might be useful to preserve link targets in the rendered output. This makes it possible to find entries based on urls linked in the content. You can instruct the renderer to add link targets in parenthesis behind the link text:

```php
$renderer = (new Renderer())->withLinkTargets();

// read more <a href="https://visuellverstehen.de">about the author</a> of this package
// becomes: read more about the author (https://visuellverstehen.de) of this package
```

### Preserving HTML tags

If you want to keep the HTML tags and/or modify the content in your own way, you can instruct the renderer not to strip them:

```php
$renderer = (new Renderer())->withHtmlTags();
```

**Note:** If you choose to preserve HTML tags, the `withLinkTargets` option (see above) will be ignored.

### Custom processor 

If you need to modify the content before it is passed on to the render process, you can optionally add a custom processor function:

```php
$renderer = new Renderer();
$renderer->setContent($searchable, 'bard_content');
// …

$renderer->process(function ($content) {
    // modify content
    
    return $content;
});
```

This allows you to e. g. remove certain sets or modify the content in any other way.

### Using a Value object directly

If you already have a Value object (`Statamic\Fields\Value`, e. g. from a fieldtype), you can pass it directly to the renderer using `setValue()` instead of `setContent()`:

```php
$renderer = new Renderer();
$renderer->setValue($fieldValue);

$content = $renderer->render();
```

The renderer will automatically resolve the entry and field handle from the Value object.

## Upgrading

### From v1.x to v2.0

Version 2.0 is a major release that aligns with Statamic v6. It contains breaking changes.

#### Requirements

- PHP 8.2+ (up from 8.1)
- Statamic 6.0+ (dropped support for v3/v4/v5)

#### Custom Processor Changes

The `process()` callback now receives **raw field data** instead of data processed through Statamic's CP pipeline.

**For most users, no changes are needed.** The data structure for filtering by `type` is identical:

```php
// This works the same in v1.x and v2.x
$renderer->process(function ($content) {
    return array_filter($content, fn ($item) => ($item['type'] ?? null) !== 'set');
});
```

However, if you were modifying inner field values within sets, those values will now be raw instead of processed through their respective fieldtypes. Adjust your callback accordingly:

```php
// v1.x - values were processed
$renderer->process(function ($content) {
    // $content[0]['text'] was already processed as a Text fieldtype value
});

// v2.x - values are raw
$renderer->process(function ($content) {
    // $content[0]['text'] is the raw string value from the entry
});
```

#### Internal Changes

- The internal `preProcess()`/`process()` round-trip has been removed
- The Renderer now uses Statamic's public fieldtype `augment()` API directly
- `renderWithoutView()` now uses the fieldtype's own Bard configuration instead of `CoreModifiers::bardHtml()`

See the [CHANGELOG](CHANGELOG.md) for full details.

## More about us

- [www.visuellverstehen.de](https://visuellverstehen.de)

## License
The MIT license (MIT). Please take a look at the [license file](LICENSE.md) for more information.
