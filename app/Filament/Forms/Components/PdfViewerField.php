<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Closure;

class PdfViewerField extends Field
{
    protected string $view = 'filament.forms.components.pdf-viewer-field';

    protected bool $enableAnnotations = true;
    protected bool $readonly = false;
    protected ?array $toolbarItems = null;
    protected ?int $maxFileSize = null;
    protected string $height = '600px';
    protected string $theme = 'auto';
    protected int $initialPage = 1;
    protected ?string $documentUrl = null;
    protected ?int $documentId = null;

    /**
     * Enable or disable annotations
     */
    public function enableAnnotations(bool|Closure $condition = true): static
    {
        $this->enableAnnotations = $this->evaluate($condition);
        return $this;
    }

    /**
     * Set the field as readonly (view-only mode)
     */
    public function readonly(bool|Closure $condition = true): static
    {
        $this->readonly = $this->evaluate($condition);
        return $this;
    }

    /**
     * Configure custom toolbar items
     */
    public function toolbarItems(array|Closure|null $items): static
    {
        $this->toolbarItems = $this->evaluate($items);
        return $this;
    }

    /**
     * Set maximum file size in bytes
     */
    public function maxFileSize(int|Closure|null $bytes): static
    {
        $this->maxFileSize = $this->evaluate($bytes);
        return $this;
    }

    /**
     * Set the viewer height
     */
    public function height(string|Closure $height): static
    {
        $this->height = $this->evaluate($height);
        return $this;
    }

    /**
     * Set the viewer theme (auto, light, dark)
     */
    public function theme(string|Closure $theme): static
    {
        $this->theme = $this->evaluate($theme);
        return $this;
    }

    /**
     * Set the initial page number to display
     */
    public function initialPage(int|Closure $page): static
    {
        $this->initialPage = $this->evaluate($page);
        return $this;
    }

    /**
     * Set document URL directly (alternative to using document ID)
     */
    public function documentUrl(string|Closure|null $url): static
    {
        $this->documentUrl = $this->evaluate($url);
        return $this;
    }

    /**
     * Set document ID for loading from database
     */
    public function documentId(int|Closure|null $id): static
    {
        $this->documentId = $this->evaluate($id);
        return $this;
    }

    /**
     * Get whether annotations are enabled
     */
    public function getEnableAnnotations(): bool
    {
        return $this->enableAnnotations;
    }

    /**
     * Get readonly state
     */
    public function getReadonly(): bool
    {
        return $this->readonly;
    }

    /**
     * Get toolbar items configuration
     */
    public function getToolbarItems(): ?array
    {
        return $this->toolbarItems;
    }

    /**
     * Get max file size
     */
    public function getMaxFileSize(): ?int
    {
        return $this->maxFileSize ?? config('nutrient.max_file_size', 50 * 1024 * 1024);
    }

    /**
     * Get viewer height
     */
    public function getHeight(): string
    {
        return $this->height;
    }

    /**
     * Get viewer theme
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * Get initial page number
     */
    public function getInitialPage(): int
    {
        return $this->initialPage;
    }

    /**
     * Get document URL
     */
    public function getDocumentUrl(): ?string
    {
        return $this->documentUrl;
    }

    /**
     * Get document ID
     */
    public function getDocumentId(): ?int
    {
        return $this->documentId;
    }

    /**
     * Configure for minimal toolbar (view-only mode)
     */
    public function minimal(): static
    {
        return $this
            ->readonly()
            ->enableAnnotations(false)
            ->toolbarItems([
                'zoom-in',
                'zoom-out',
                'zoom-mode',
                'spacer',
                'search',
                'spacer',
                'print',
                'download',
            ]);
    }

    /**
     * Configure for full editing mode
     */
    public function fullEditor(): static
    {
        return $this
            ->readonly(false)
            ->enableAnnotations(true)
            ->toolbarItems([
                'sidebar-thumbnails',
                'sidebar-document-outline',
                'sidebar-annotations',
                'pager',
                'zoom-in',
                'zoom-out',
                'zoom-mode',
                'spacer',
                'annotate',
                'ink',
                'highlighter',
                'text',
                'note',
                'text-highlighter',
                'ink-eraser',
                'spacer',
                'search',
                'spacer',
                'print',
                'download',
            ]);
    }

    /**
     * Configure for annotation review mode
     */
    public function reviewMode(): static
    {
        return $this
            ->readonly(false)
            ->enableAnnotations(true)
            ->toolbarItems([
                'sidebar-annotations',
                'pager',
                'zoom-in',
                'zoom-out',
                'spacer',
                'note',
                'highlighter',
                'spacer',
                'search',
                'spacer',
                'print',
            ]);
    }
}
