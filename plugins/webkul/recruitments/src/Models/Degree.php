<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;

/**
 * Degree Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $sort
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class Degree extends Model implements Sortable
{
    use SortableTrait;

    protected $table = 'recruitments_degrees';

    protected $fillable = ['name', 'sort', 'creator_id'];

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
        return $this->belongsTo(User::class, 'creator_id');
    }
}
