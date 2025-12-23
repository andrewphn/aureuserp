<?php

namespace Webkul\TcsCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Webkul\Employee\Models\Employee;

class TeamMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tcs_team_members';

    protected $fillable = [
        'name',
        'slug',
        'role',
        'title',
        'bio',
        'full_bio',
        'photo',
        'skills',
        'certifications',
        'social_links',
        'email',
        'phone',
        'years_experience',
        'start_date',
        'is_published',
        'featured',
        'position',
        'employee_id',
        'zoho_employee_id',
        'zoho_department',
        'zoho_role',
        'zoho_status',
        'zoho_join_date',
        'zoho_leave_date',
    ];

    protected $casts = [
        'skills' => 'array',
        'certifications' => 'array',
        'social_links' => 'array',
        'is_published' => 'boolean',
        'featured' => 'boolean',
        'start_date' => 'date',
        'zoho_join_date' => 'datetime',
        'zoho_leave_date' => 'datetime',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        return Storage::url($this->photo);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('name');
    }
}
