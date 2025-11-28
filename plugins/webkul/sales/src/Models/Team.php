<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Sale\Database\Factories\TeamFactory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Team Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $sort
 * @property int $company_id
 * @property int $user_id
 * @property string|null $color
 * @property int $creator_id
 * @property string|null $name
 * @property bool $is_active
 * @property string|null $invoiced_target
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection $members
 *
 */
class Team extends Model implements Sortable
{
    use HasChatter, HasFactory, HasLogActivity, SoftDeletes, SortableTrait;

    protected $table = 'sales_teams';

    protected $fillable = [
        'sort',
        'company_id',
        'user_id',
        'color',
        'creator_id',
        'name',
        'is_active',
        'invoiced_target',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    protected array $logAttributes = [
        'name',
        'company.name'    => 'Company',
        'user.name'       => 'Team Leader',
        'creator.name'    => 'Creator',
        'is_active'       => 'Status',
        'invoiced_target' => 'Invoiced Target',
    ];

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * User (Team Leader)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Members
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'sales_team_members', 'team_id', 'user_id');
    }

    /**
     * New Factory
     *
     * @return TeamFactory
     */
    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }
}
