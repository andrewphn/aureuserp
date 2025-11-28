<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * Tag Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $color
 * @property string|null $name
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class Tag extends Model
{
    use HasFactory;

    protected $table = 'sales_tags';

    protected $fillable = [
        'color',
        'name',
        'creator_id',
    ];

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
