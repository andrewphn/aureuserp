<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Bank Statement Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $company_id
 * @property int $journal_id
 * @property string|null $created_by
 * @property string|null $name
 * @property string|null $reference
 * @property string|null $first_line_index
 * @property \Carbon\Carbon|null $date
 * @property string|null $balance_start
 * @property string|null $balance_end
 * @property string|null $balance_end_real
 * @property bool $is_completed
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $journal
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class BankStatement extends Model
{
    use HasFactory;

    protected $table = 'accounts_bank_statements';

    protected $fillable = [
        'company_id',
        'journal_id',
        'created_by',
        'name',
        'reference',
        'first_line_index',
        'date',
        'balance_start',
        'balance_end',
        'balance_end_real',
        'is_completed',
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

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

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
