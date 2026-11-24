<?php

namespace App\Services;

use App\Models\SewingNumberSequence;
use Illuminate\Support\Facades\DB;

class SewingNumberService
{
    /**
     * Generate the next inspection sheet number safely under concurrent requests.
     */
    public function nextSheetNumber(): string
    {
        return $this->next('sewing_inspection');
    }

    public function next(string $key): string
    {
        return DB::transaction(function () use ($key) {
            $sequence = SewingNumberSequence::where('key', $key)->lockForUpdate()->first();

            if (! $sequence) {
                SewingNumberSequence::create(['key' => $key]);
                $sequence = SewingNumberSequence::where('key', $key)->lockForUpdate()->first();
            }

            $number = $sequence->next_number;
            $sequence->next_number = $number + 1;
            $sequence->save();

            return $sequence->prefix.str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT);
        });
    }
}
