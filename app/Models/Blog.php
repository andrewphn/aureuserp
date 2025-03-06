<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;

class Blog extends Model
{
    /**
     * Get image url for the product image.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->image);
    }

    public function getReadingTimeAttribute()
    {
        $wordCount = str_word_count(strip_tags($this->content));

        $minutes = ceil($wordCount / 200);

        return $minutes . ' min read';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
