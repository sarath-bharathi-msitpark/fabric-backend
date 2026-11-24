<?php

namespace App\Models;

use App\Models\Concerns\LogsSewingAudit;
use Illuminate\Database\Eloquent\Model;

class SewingMachine extends Model
{
    use LogsSewingAudit;

    protected $fillable = ['machine_no', 'type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
