<?php

namespace VV\ContentRenderer;

use Exception;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;
use Statamic\Fieldtypes\Bard\Augmentor;
use Statamic\Fieldtypes\Replicator;
use Statamic\Modifiers\CoreModifiers;

class Renderer
{
    protected $augmentor;
    protected $customProcessor;
    protected $entry;
    protected $fieldHandle;
    protected $fieldValue;
    protected $viewPath;
    protected $withHtmlTags = false;
    protected $withLinkTargets = false;

    public function process(callable $callback): self
    {
        $this->customProcessor = $callback;

        return $this;
    }

    public function render(): string
    {
        return $this->renderContent();
    }

    public function setContent(Entry|string $entry, string $fieldHandle): self
    {
        if ($entry instanceof Entry) {
            $this->entry = $entry;
        } else {
            $this->entry = EntryFacade::find($entry) ?? false;
        }

        if (! $this->entry) {
            return $this;
        }

        if (
            ! $this->entry->has($fieldHandle) &&
            $this->entry->origin() &&
            $this->entry->origin()->has($fieldHandle)
        ) {
            $this->entry = $this->entry->origin();
        }

        $this->fieldHandle = $fieldHandle;
        $this->fieldValue = $this->entry->augmentedValue($this->fieldHandle);

        return $this;
    }

    public function setValue(Value $fieldValue): self
    {
        $this->fieldValue = $fieldValue;

        if (! $this->entry && $entry = $fieldValue->augmentable()) {
            $this->entry = $entry;
        }

        if (! $this->fieldHandle) {
            $this->fieldHandle = $fieldValue->handle();
        }

        return $this;
    }

    public function setView(string|null $view): self
    {
        $this->viewPath = $view;

        return $this;
    }

    public function withHtmlTags(): self
    {
        $this->withHtmlTags = true;

        return $this;
    }

    public function withLinkTargets(): self
    {
        $this->withLinkTargets = true;

        return $this;
    }

    public function withoutHtmlTags(): self
    {
        $this->withHtmlTags = false;

        return $this;
    }

    public function withoutLinkTargets(): self
    {
        $this->withLinkTargets = false;

        return $this;
    }

    protected function customProcess($content)
    {
        if ($this->customProcessor && is_callable($this->customProcessor)) {
            $processor = $this->customProcessor;
            $content = $processor($content);
        }

        return $content;
    }

    protected function renderContent(): string
    {
        if (! $this->entry || ! $this->fieldHandle) {
            return '';
        }

        // make sure we definitely have field data
        // (might be a problem with e. g. old content files)
        if (! $this->fieldValue && ! $this->fieldValue = $this->entry->augmentedValue($this->fieldHandle)) {
            return '';
        }

        $fieldtype = $this->fieldValue->field()?->fieldtype();

        if ($fieldtype instanceof Bard) {
            return $this->renderBard($fieldtype);
        }

        if ($fieldtype instanceof Replicator) {
            return $this->renderReplicator($fieldtype);
        }

        return '';
    }

    protected function renderBard(Bard $bard): string
    {
        if (! $this->augmentor) {
            $this->augmentor = (new Augmentor($bard))->withStatamicImageUrls();
        }

        $content = $this->fieldValue->raw();
        $content = $bard->preProcess($content);
        $content = $bard->process($content);
        $content = $this->customProcess($content);

        $content = $this->augmentor->augment($content);

        return $this->viewPath ? $this->renderWithView($content) : $this->renderWithoutView();
    }

    protected function renderReplicator(Replicator $replicator): string
    {
        $content = $this->fieldValue->raw();
        $content = $replicator->preProcess($content);
        $content = $replicator->process($content);
        $content = $this->customProcess($content);

        $content = $replicator->augment($content);

        return $this->viewPath ? $this->renderWithView($content) : '';
    }

    protected function renderWithView($content): string
    {
        if (! $this->viewPath) {
            return '';
        }

        try {
            $content = (string) view($this->viewPath, [$this->fieldHandle => $content]);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return $this->sanitizeContent($content);
    }

    protected function renderWithoutView(): string
    {
        $content = (new CoreModifiers)->bardHtml($this->fieldValue);

        return $this->sanitizeContent($content);
    }

    protected function sanitizeContent(string $content): string
    {
        // remove excess whitespace and empty lines
        $content = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/', '', $content);
        $content = preg_replace('/\n/', '', $content);
        $content = preg_replace('/\s\s+/', ' ', $content);
        $content = trim($content);

        // add whitespace between html tags to separate words
        $content = preg_replace('/\>[\s+]?\</', '> <', $content);

        if (! $this->withHtmlTags) {
            // optionally extract link targets and add them in () behind the link name
            if ($this->withLinkTargets) {
                $content = preg_replace('/<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/', '$2 ($1)', $content);
            }

            // add whitespace between strings within html tags
            $content = preg_replace('/\>(\w+)\<\//', '/> $1 <', $content);
            $content = strip_tags($content);
        }

        // separate fullstops and starting words that were merged when removing tags
        $content = preg_replace('/(\w)\.(\w+)\s/', '$1. $2 ', $content);

        return $content;
    }
}
