<?php

namespace Webkul\TableViews\Filament\Components;

use Closure;
use Filament\Schemas\Components\Tabs\Tab;
use Webkul\TableViews\Models\TableViewFavorite;

/**
 * Preset View class
 *
 * @see \Filament\Resources\Resource
 */
class PresetView extends Tab
{
    protected string|Closure|null $id = null;

    protected string|Closure|null $color = null;

    protected bool|Closure $isDefault = false;

    protected bool|Closure $isFavorite = false;

    protected bool|Closure $isEditable = false;

    protected bool|Closure $isReplaceable = false;

    protected bool|Closure $isDeletable = false;

    protected static mixed $cachedFavoriteTableViews;

    public function getFavoriteTableViews(): mixed
    {
        return TableViewFavorite::query()
            ->where('user_id', filament()->auth()->id())
            ->get();
    }

    public function getCachedFavoriteTableViews(): mixed
    {
        return self::$cachedFavoriteTableViews ??= $this->getFavoriteTableViews();
    }

    /**
     * Color
     *
     * @param string|Closure|null $color
     * @return static
     */
    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getModel(): ?string
    {
        return null;
    }

    /**
     * Favorite
     *
     * @param bool|Closure $condition
     * @return static
     */
    public function favorite(bool|Closure $condition = true): static
    {
        $this->isFavorite = $condition;

        return $this;
    }

    /**
     * Set As Default
     *
     * @param bool|Closure $condition
     * @return static
     */
    public function setAsDefault(bool|Closure $condition = true): static
    {
        $this->isDefault = $condition;

        return $this;
    }

    /**
     * Is Default
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return (bool) $this->evaluate($this->isDefault);
    }

    /**
     * Is Favorite
     *
     * @param string|int|null $id The unique identifier
     * @return bool
     */
    public function isFavorite(string|int|null $id = null): bool
    {
        $tableViewFavorite = $this->getCachedFavoriteTableViews()
            ->where('view_type', 'preset')
            ->where('view_key', $id)
            ->first();

        return (bool) ($tableViewFavorite?->is_favorite ?? $this->evaluate($this->isFavorite));
    }

    /**
     * Is Editable
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return $this->isEditable;
    }

    /**
     * Is Replaceable
     *
     * @return bool
     */
    public function isReplaceable(): bool
    {
        return $this->isReplaceable;
    }

    /**
     * Is Deletable
     *
     * @return bool
     */
    public function isDeletable(): bool
    {
        return $this->isDeletable;
    }

    public function getVisibilityIcon(): string
    {
        return 'heroicon-o-lock-closed';
    }

    public function getColor(): string|array|null
    {
        return $this->evaluate($this->color);
    }
}
