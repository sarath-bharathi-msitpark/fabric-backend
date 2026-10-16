<?php

namespace App\Services;

use App\Models\CuttingNumberSequence;
use Illuminate\Support\Facades\DB;

class CuttingNumberService
{
    /**
     * Generate the next audit number safely under concurrent requests.
     */
    public function nextAuditNumber(): string
    {
        return $this->next('cutting_audit');
    }

    public function next(string $key): string
    {
        return DB::transaction(function () use ($key) {
            $sequence = CuttingNumberSequence::where('key', $key)->lockForUpdate()->first();

            if (! $sequence) {
                CuttingNumberSequence::create(['key' => $key]);
                $sequence = CuttingNumberSequence::where('key', $key)->lockForUpdate()->first();
            }

            $number = $sequence->next_number;
            $sequence->next_number = $number + 1;
            $sequence->save();

            return $sequence->prefix.str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT);
        });
    }
}
