<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Account Account Tag Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $account_id
 * @property int $account_tag_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $account
 * @property-read \Illuminate\Database\Eloquent\Model|null $accountTag
 *
 */
class AccountAccountTag extends Model
{
    protected $table = 'accounts_account_account_tags';

    protected $fillable = [
        'account_id',
        'account_tag_id',
    ];

    public $timestamps = false;

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
     * Account Tag
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accountTag()
    {
        return $this->belongsTo(Tag::class);
    }
}
