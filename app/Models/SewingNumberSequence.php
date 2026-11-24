<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingNumberSequence extends Model
{
    protected $fillable = ['key', 'prefix', 'next_number', 'padding'];

    protected $casts = ['next_number' => 'integer', 'padding' => 'integer'];
}
