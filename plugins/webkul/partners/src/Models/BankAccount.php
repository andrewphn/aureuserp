<?php

namespace Webkul\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Partner\Database\Factories\BankAccountFactory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Bank;

/**
 * Bank Account Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property float $account_number
 * @property float $account_holder_name
 * @property bool $is_active
 * @property bool $can_send_money
 * @property int $creator_id
 * @property int $partner_id
 * @property int $bank_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $bank
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'partners_bank_accounts';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'account_number',
        'account_holder_name',
        'is_active',
        'can_send_money',
        'creator_id',
        'partner_id',
        'bank_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'is_active'      => 'boolean',
        'can_send_money' => 'boolean',
    ];

    /**
     * Bank
     *
     * @return BelongsTo
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Partner
     *
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Bootstrap any application services.
     */
    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bankAccount) {
            $bankAccount->account_holder_name = $bankAccount->partner->name;
        });

        static::updating(function ($bankAccount) {
            $bankAccount->account_holder_name = $bankAccount->partner->name;
        });
    }

    /**
     * New Factory
     *
     * @return BankAccountFactory
     */
    protected static function newFactory(): BankAccountFactory
    {
        return BankAccountFactory::new();
    }
}
