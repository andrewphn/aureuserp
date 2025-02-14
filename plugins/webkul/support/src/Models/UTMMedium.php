<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

class UTMMedium extends Model
{
    protected $table = 'utm_mediums';

    protected $fillable = ['name', 'creator_id', 'is_active'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
