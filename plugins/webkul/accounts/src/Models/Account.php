<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Account\Enums\AccountType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Currency;

/**
 * Account Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $currency_id
 * @property int $creator_id
 * @property mixed $account_type
 * @property string|null $name
 * @property string|null $code
 * @property string|null $note
 * @property bool $deprecated
 * @property bool $reconcile
 * @property bool $non_trade
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection $taxes
 * @property-read \Illuminate\Database\Eloquent\Collection $tags
 * @property-read \Illuminate\Database\Eloquent\Collection $journals
 *
 */
class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts_accounts';

    protected $fillable = [
        'currency_id',
        'creator_id',
        'account_type',
        'name',
        'code',
        'note',
        'deprecated',
        'reconcile',
        'non_trade',
    ];

    protected $casts = [
        'deprecated'   => 'boolean',
        'reconcile'    => 'boolean',
        'non_trade'    => 'boolean',
        'account_type' => AccountType::class,
    ];

    /**
     * Currency
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Taxes
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'accounts_account_taxes', 'account_id', 'tax_id');
    }

    /**
     * Tags
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'accounts_account_account_tags', 'account_id', 'account_tag_id');
    }

    /**
     * Journals
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function journals()
    {
        return $this->belongsToMany(Journal::class, 'accounts_account_journals', 'account_id', 'journal_id');
    }
}
