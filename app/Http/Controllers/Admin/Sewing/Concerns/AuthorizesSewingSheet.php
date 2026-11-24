<?php

namespace App\Http\Controllers\Admin\Sewing\Concerns;

use App\Models\SewingInspectionSheet;
use Illuminate\Http\Request;

trait AuthorizesSewingSheet
{
    protected function authorizeSheet(Request $request, SewingInspectionSheet $sheet): void
    {
        $visible = SewingInspectionSheet::visibleTo($request->user())
            ->whereKey($sheet->getKey())
            ->exists();

        abort_unless($visible, 403);
    }
}
