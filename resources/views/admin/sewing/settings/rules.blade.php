@extends('layouts.app')

@section('title', 'Inspection Rules')
@section('header', 'Inspection Rules')

@section('content')
<form method="POST" action="{{ route('admin.sewing.settings.rules.update') }}" class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">@csrf @method('PUT')
    <p class="text-sm text-gray-500 mb-4">These thresholds drive the Green / Amber / Red result for every grid cell. Changes apply to new entries.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Pieces per audit (BR-01) *</label>
            <input type="number" name="pieces_per_audit" min="1" max="20" value="{{ old('pieces_per_audit', $rule->pieces_per_audit) }}" required class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Default rounds *</label>
            <input type="number" name="default_rounds" min="1" max="12" value="{{ old('default_rounds', $rule->default_rounds) }}" required class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Green max defects (BR-02) *</label>
            <input type="number" name="green_max" min="0" max="20" value="{{ old('green_max', $rule->green_max) }}" required class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Amber max defects (BR-02) *</label>
            <input type="number" name="amber_max" min="0" max="20" value="{{ old('amber_max', $rule->amber_max) }}" required class="w-full rounded-md border-gray-300 text-sm">
        </div>
    </div>
    <p class="mt-3 text-xs text-gray-400">Anything above the Amber max is Red. Default: 0 = Green, 1&ndash;2 = Amber, 3+ = Red.</p>
    <div class="mt-6 flex justify-end">
        <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save rules</button>
    </div>
</form>
@endsection
