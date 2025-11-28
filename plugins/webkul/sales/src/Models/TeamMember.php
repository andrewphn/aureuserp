<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Team Member Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $team_id
 * @property int $user_id
 *
 */
class TeamMember extends Model
{
    public $timestamps = false;

    protected $table = 'sales_team_members';

    protected $fillable = [
        'team_id',
        'user_id',
    ];
}
