<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Lead\Enums\LeadSource;
use Webkul\Lead\Enums\LeadStatus;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Lead Eloquent model
 *
 * @property int $id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $company_name
 * @property string $status
 * @property string|null $source
 * @property string|null $message
 * @property string|null $project_type
 * @property string|null $budget_range
 * @property string|null $timeline
 * @property array|null $form_data
 * @property string|null $hubspot_contact_id
 * @property string|null $hubspot_deal_id
 * @property int|null $assigned_user_id
 * @property int|null $partner_id
 * @property int|null $project_id
 * @property \Carbon\Carbon|null $converted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read User|null $assignedUser
 * @property-read User|null $creator
 * @property-read Partner|null $partner
 * @property-read Project|null $project
 * @property-read Company|null $company
 */
class Lead extends Model implements HasMedia
{
    use HasChatter, HasFactory, HasLogActivity, InteractsWithMedia, SoftDeletes;

    /**
     * Table name.
     */
    protected $table = 'leads';

    /**
     * Fillable.
     */
    protected $fillable = [
        // Contact Info
        'first_name',
        'last_name',
        'email',
        'phone',
        'company_name',

        // Lead Management
        'status',
        'source',
        'message',

        // Classification
        'lead_source_detail',
        'market_segment',
        'primary_interest',
        'lead_type',
        'preferred_contact_method',

        // Project Details
        'project_type',
        'project_type_other',
        'project_phase',
        'budget_range',
        'timeline',
        'timeline_start_date',
        'timeline_completion_date',
        'project_description',
        'additional_information',
        'design_style',
        'design_style_other',
        'finish_choices',
        'wood_species',
        'referral_source_other',

        // JSON Data
        'form_data',
        'questionnaire_data',
        'ai_analysis_results',

        // CRM Integration
        'hubspot_contact_id',
        'hubspot_deal_id',

        // Assignment & Conversion
        'assigned_user_id',
        'partner_id',
        'project_id',
        'converted_at',
        'disqualification_reason',

        // Address
        'street1',
        'street2',
        'city',
        'state',
        'zip',
        'country',
        'project_address_notes',

        // File attachments
        'inspiration_images',
        'technical_drawings',
        'project_documents',

        // Consent
        'processing_consent',
        'communication_consent',

        // Tracking
        'creator_id',
        'company_id',
    ];

    /**
     * Casts.
     */
    protected $casts = [
        'status' => LeadStatus::class,
        'source' => LeadSource::class,
        'form_data' => 'array',
        'questionnaire_data' => 'array',
        'ai_analysis_results' => 'array',
        'finish_choices' => 'array',
        'inspiration_images' => 'array',
        'technical_drawings' => 'array',
        'project_documents' => 'array',
        'timeline_start_date' => 'date',
        'timeline_completion_date' => 'date',
        'converted_at' => 'datetime',
        'processing_consent' => 'boolean',
        'communication_consent' => 'boolean',
    ];

    /**
     * Register media collections for file uploads
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('inspiration_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

        $this->addMediaCollection('technical_drawings')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.dwg']);

        $this->addMediaCollection('project_documents')
            ->acceptsMimeTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    /**
     * Log activity attributes
     */
    protected array $logAttributes = [
        'first_name',
        'last_name',
        'email',
        'status',
        'source',
        'assignedUser.name' => 'Assigned To',
        'partner.name' => 'Partner',
        'project.name' => 'Project',
    ];

    /**
     * Assigned User
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Partner (when converted)
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Project (when converted)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get days since submission
     */
    public function getDaysSinceSubmissionAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if lead is new (created within last 24 hours)
     */
    public function getIsNewAttribute(): bool
    {
        return $this->created_at->diffInHours(now()) < 24;
    }

    /**
     * Check if lead is converted
     */
    public function getIsConvertedAttribute(): bool
    {
        return $this->status === LeadStatus::CONVERTED;
    }

    /**
     * Check if lead can be converted
     */
    public function canConvert(): bool
    {
        return ! $this->is_converted
            && $this->status !== LeadStatus::DISQUALIFIED
            && ! empty($this->email);
    }

    /**
     * Create lead from contact form data
     */
    public static function createFromContactForm(array $data): self
    {
        // Extract main fields from form data
        $lead = new self([
            'first_name' => $data['firstname'] ?? $data['first_name'] ?? null,
            'last_name' => $data['lastname'] ?? $data['last_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company_name' => $data['company'] ?? $data['company_name'] ?? null,

            'status' => LeadStatus::NEW,
            'source' => self::determineSource($data),
            'referral_source_other' => $data['referralsourceother'] ?? $data['referral_source_other'] ?? null,
            'message' => $data['message'] ?? $data['project_description'] ?? null,

            'preferred_contact_method' => $data['contactpreferred'] ?? $data['preferred_contact_method'] ?? null,
            'lead_source_detail' => $data['previous_woodworking_experience'] ?? null,

            // Project details
            'project_type' => is_array($data['project_type'] ?? null)
                ? implode(', ', $data['project_type'])
                : ($data['project_type'] ?? null),
            'project_type_other' => $data['project_type_other'] ?? null,
            'project_phase' => $data['project_phase'] ?? null,
            'budget_range' => $data['budget_range'] ?? null,
            'timeline' => $data['timeline_start_date'] ?? $data['timeline'] ?? null,
            'timeline_start_date' => $data['timeline_start_date'] ?? null,
            'timeline_completion_date' => $data['timeline_completion_date'] ?? null,
            'project_description' => $data['project_description'] ?? null,
            'additional_information' => $data['additional_information'] ?? null,
            'design_style' => is_array($data['design_style'] ?? null)
                ? implode(', ', $data['design_style'])
                : ($data['design_style'] ?? null),
            'design_style_other' => $data['design_style_other'] ?? null,
            'finish_choices' => $data['finish_choices'] ?? null,
            'wood_species' => $data['wood_species'] ?? null,

            // Address
            'street1' => $data['project_address_street1'] ?? $data['street1'] ?? null,
            'street2' => $data['project_address_street2'] ?? $data['street2'] ?? null,
            'city' => $data['project_address_city'] ?? $data['city'] ?? null,
            'state' => $data['project_address_state'] ?? $data['state'] ?? null,
            'zip' => $data['project_address_zip'] ?? $data['zip'] ?? null,
            'country' => $data['project_address_country'] ?? $data['country'] ?? 'United States',
            'project_address_notes' => $data['project_address_notes'] ?? null,

            // Consent
            'processing_consent' => (bool) ($data['processing_consent'] ?? false),
            'communication_consent' => (bool) ($data['communication_consent'] ?? false),

            // Store complete form data
            'form_data' => $data,
        ]);

        $lead->save();

        return $lead;
    }

    /**
     * Determine lead source from form data
     */
    protected static function determineSource(array $data): LeadSource
    {
        // Check for explicit source field
        $sourceField = $data['source'] ?? $data['lead_source'] ?? null;

        if (is_array($sourceField)) {
            // Take first source from array
            $sourceField = $sourceField[0] ?? null;
        }

        if ($sourceField) {
            $sourceField = strtolower(str_replace(' ', '_', $sourceField));

            return match ($sourceField) {
                'google', 'google_search' => LeadSource::GOOGLE,
                'referral' => LeadSource::REFERRAL,
                'social_media', 'facebook', 'instagram' => LeadSource::SOCIAL_MEDIA,
                'houzz' => LeadSource::HOUZZ,
                'home_show' => LeadSource::HOME_SHOW,
                'designer', 'architect' => LeadSource::DESIGNER,
                'previous_client', 'returning' => LeadSource::PREVIOUS_CLIENT,
                default => LeadSource::WEBSITE,
            };
        }

        // Default to website for online submissions
        return LeadSource::WEBSITE;
    }

    /**
     * Scope for inbox leads (not converted, not disqualified)
     */
    public function scopeInbox($query)
    {
        return $query->whereNotIn('status', [
            LeadStatus::CONVERTED,
            LeadStatus::DISQUALIFIED,
        ]);
    }

    /**
     * Scope for new leads
     */
    public function scopeNew($query)
    {
        return $query->where('status', LeadStatus::NEW);
    }

    /**
     * Scope by assigned user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    /**
     * Scope unassigned leads
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_user_id');
    }
}
