<?php

namespace Webkul\TableViews\Filament\Components;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Saved View class
 *
 * @see \Filament\Resources\Resource
 */
class SavedView extends PresetView
{
    protected Model|array|string|Closure|null $model = null;

    /**
     * Model
     *
     * @param Model|array|string|Closure|null $model The model instance
     * @return static
     */
    public function model(Model|array|string|Closure|null $model = null): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
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
            ->where('view_type', 'saved')
            ->where('view_key', $id ?? $this->model->id)
            ->first();

        return (bool) ($tableViewFavorite?->is_favorite ?? $this->isFavorite);
    }

    /**
     * Is Public
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->getRecord()->is_public;
    }

    /**
     * Is Editable
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return $this->getRecord()->user_id === filament()->auth()->id();
    }

    /**
     * Is Replaceable
     *
     * @return bool
     */
    public function isReplaceable(): bool
    {
        return $this->getRecord()->user_id === filament()->auth()->id();
    }

    /**
     * Is Deletable
     *
     * @return bool
     */
    public function isDeletable(): bool
    {
        return $this->getRecord()->user_id === filament()->auth()->id();
    }

    public function getVisibilityIcon(): string
    {
        return $this->isPublic() ? 'heroicon-o-eye' : 'heroicon-o-user';
    }
}
