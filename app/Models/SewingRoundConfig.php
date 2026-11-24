<?php

namespace App\Models;

use App\Models\Concerns\LogsSewingAudit;
use Illuminate\Database\Eloquent\Model;

class SewingRoundConfig extends Model
{
    use LogsSewingAudit;

    protected $fillable = ['round_no', 'start_time', 'end_time', 'is_active'];

    protected $casts = ['round_no' => 'integer', 'is_active' => 'boolean'];
}
