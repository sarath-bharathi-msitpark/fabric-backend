@php($defect = $defect ?? null)
<div class="space-y-3">
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Defect type *</label>
            <select name="defect_type" class="w-full rounded-md border-gray-300 text-sm" required>
                @foreach(\App\Models\SewingDefectMaster::TYPES as $key => $label)
                    <option value="{{ $key }}" @selected(($defect->defect_type ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">CAPA code *</label>
            <input type="text" name="capa_code" value="{{ $defect->capa_code ?? '' }}" required class="w-full rounded-md border-gray-300 text-sm font-mono">
        </div>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Defect name *</label>
        <input type="text" name="defect_name" value="{{ $defect->defect_name ?? '' }}" required class="w-full rounded-md border-gray-300 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Root cause *</label>
        <textarea name="root_cause" rows="2" required class="w-full rounded-md border-gray-300 text-sm">{{ $defect->root_cause ?? '' }}</textarea>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Corrective action *</label>
        <textarea name="corrective_action" rows="2" required class="w-full rounded-md border-gray-300 text-sm">{{ $defect->corrective_action ?? '' }}</textarea>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Preventive action *</label>
        <textarea name="preventive_action" rows="2" required class="w-full rounded-md border-gray-300 text-sm">{{ $defect->preventive_action ?? '' }}</textarea>
    </div>
    @if($defect)
        <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($defect->is_active)> Active</label>
    @endif
</div>
