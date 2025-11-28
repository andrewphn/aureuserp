<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Candidate Applicant Category Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $candidate_id
 * @property int $applicant_category_id
 *
 */
class CandidateApplicantCategory extends Model
{
    protected $table = 'recruitments_candidate_applicant_categories';

    protected $fillable = ['candidate_id', 'applicant_category_id'];

    public $timestamps = false;
}
