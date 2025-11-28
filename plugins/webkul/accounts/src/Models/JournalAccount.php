<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Journal Account Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $account_id
 * @property int $journal_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $account
 * @property-read \Illuminate\Database\Eloquent\Model|null $journal
 *
 */
class JournalAccount extends Model
{
    protected $table = 'accounts_journal_accounts';

    protected $timestamps = false;

    protected $fillable = [
        'account_id',
        'journal_id',
    ];

    /**
     * Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Journal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}
