<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'template_content',
        'template_path',
        'description',
        'is_default',
        'variables',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'variables' => 'array',
    ];

    /**
     * Available template types
     */
    public const TYPE_PROPOSAL = 'proposal';
    public const TYPE_INVOICE_DEPOSIT = 'invoice_deposit';
    public const TYPE_INVOICE_PROGRESS = 'invoice_progress';
    public const TYPE_INVOICE_FINAL = 'invoice_final';
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_BOL = 'bol';

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this template
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the template content (from file or database)
     */
    public function getContent(): ?string
    {
        if ($this->template_content) {
            return $this->template_content;
        }

        if ($this->template_path && file_exists($this->template_path)) {
            return file_get_contents($this->template_path);
        }

        return null;
    }

    /**
     * Scope to get default templates
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get all available template types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PROPOSAL => __('Proposal'),
            self::TYPE_INVOICE_DEPOSIT => __('Invoice - Deposit (30%)'),
            self::TYPE_INVOICE_PROGRESS => __('Invoice - Progress'),
            self::TYPE_INVOICE_FINAL => __('Invoice - Final Payment'),
            self::TYPE_CONTRACT => __('Contract'),
            self::TYPE_RECEIPT => __('Receipt'),
            self::TYPE_BOL => __('Bill of Lading'),
        ];
    }
}
