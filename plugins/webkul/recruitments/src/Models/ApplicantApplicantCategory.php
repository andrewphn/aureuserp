<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Applicant Applicant Category Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $applicant_id
 * @property int $applicant_category_id
 *
 */
class ApplicantApplicantCategory extends Model
{
    protected $table = 'recruitments_applicant_applicant_categories';

    protected $fillable = ['applicant_id', 'applicant_category_id'];

    public $timestamps = false;
}
