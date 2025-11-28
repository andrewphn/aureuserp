<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;

/**
 * Utm Stage Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property string|null $name
 * @property string|null $created_by
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class UtmStage extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $table = 'utm_stages';

    protected $fillable = [
        'sort',
        'name',
        'created_by',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
