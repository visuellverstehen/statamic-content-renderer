# Content Renderer

A Statamic addon that renders content from bard and replicator fields to make their content searchable with [search transformers](https://statamic.dev/search#transforming-fields).

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

When using the Content Renderer within a search transformer, it might be useful to be able to preserve link targets in the rendered output, to be able to find entries based on urls linked in the content. You can instruct the renderer to add link targets in parenthesis behind the link text:

```php
$renderer = (new Renderer())->$withLinkTargets();

// read more <a href="https://visuellverstehen.de">about the author</a> of this package
// becomes: read more about the author (https://visuellverstehen.de) of this package
```

### Preserving HTML tags

If you want to keep the HTML tags and/or modify the content in your own way, you can instruct the renderer to keep them:

```php
$renderer = (new Renderer())->withHtmlTags();
```

When you choose to preserve HTML tags, the `withLinkTargets` option (see above) will be ignored.

## More about us

- [www.visuellverstehen.de](https://visuellverstehen.de)

## License
The MIT license (MIT). Please take a look at the [license file](LICENSE.md) for more information.