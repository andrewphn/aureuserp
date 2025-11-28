<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Account\Models\Journal;
use Webkul\Sale\Enums\OrderDisplayType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Order Template Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property int $company_id
 * @property string|null $number_of_days
 * @property int $creator_id
 * @property string|null $name
 * @property string|null $note
 * @property int $journal_id
 * @property bool $is_active
 * @property string|null $require_signature
 * @property string|null $require_payment
 * @property string|null $prepayment_percentage
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $journal
 *
 */
class OrderTemplate extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $table = 'sales_order_templates';

    protected $fillable = [
        'sort',
        'company_id',
        'number_of_days',
        'creator_id',
        'name',
        'note',
        'journal_id',
        'is_active',
        'require_signature',
        'require_payment',
        'prepayment_percentage',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
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
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Journal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    /**
     * Products
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this
            ->hasMany(OrderTemplateProduct::class, 'order_template_id')
            ->whereNull('display_type');
    }

    /**
     * Sections
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sections()
    {
        return $this
            ->hasMany(OrderTemplateProduct::class, 'order_template_id')
            ->where('display_type', OrderDisplayType::SECTION->value);
    }

    /**
     * Notes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this
            ->hasMany(OrderTemplateProduct::class, 'order_template_id')
            ->where('display_type', OrderDisplayType::NOTE->value);
    }
}
