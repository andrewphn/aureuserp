<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;

/**
 * Refuse Reason Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $creator_id
 * @property string|null $sort
 * @property string|null $name
 * @property string|null $template
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class RefuseReason extends Model implements Sortable
{
    use SortableTrait;

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    protected $table = 'recruitments_refuse_reasons';

    protected $fillable = ['creator_id', 'sort', 'name', 'template', 'is_active'];

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
