<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function resolve(Request $request, Alert $alert)
    {
        $this->authorize('resolve', $alert);
        $data = $request->validate(['resolution_note' => 'nullable|string|max:1000']);

        $alert->update([
            'is_resolved' => true,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'resolution_note' => $data['resolution_note'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Alert resolved.');
    }
}
