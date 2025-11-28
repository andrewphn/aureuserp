<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Stage Job Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $stage_id
 * @property int $job_id
 *
 */
class StageJob extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'recruitments_stages_jobs';

    protected $fillable = [
        'stage_id',
        'job_id',
    ];
}
