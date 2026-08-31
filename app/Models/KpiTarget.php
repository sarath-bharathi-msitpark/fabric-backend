<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiTarget extends Model
{
    use HasFactory;

    protected $fillable = ['kpi_key', 'target_value', 'comparison'];

    protected $casts = ['target_value' => 'decimal:2'];
}
