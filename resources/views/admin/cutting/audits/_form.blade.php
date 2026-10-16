@csrf
@if(isset($audit)) @method('PUT') @endif

<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <h3 class="font-semibold text-gray-800 text-sm mb-3">Order Header</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Audit Date *</label>
            <input type="date" name="audit_date" required value="{{ old('audit_date', optional($audit->audit_date ?? null)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Cutting Table *</label>
            <select name="cutting_table_id" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="Select table">
                <option value="">Select table</option>
                @foreach($tables as $table)<option value="{{ $table->id }}" @selected(old('cutting_table_id', $audit->cutting_table_id ?? '')==$table->id)>{{ $table->table_no }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Shift</label>
            <input type="text" name="shift" value="{{ old('shift', $audit->shift ?? '') }}" placeholder="e.g. A / B" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Buyer</label>
            <select name="buyer_id" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="Select buyer">
                <option value="">Select buyer</option>
                @foreach($buyers as $buyer)<option value="{{ $buyer->id }}" @selected(old('buyer_id', $audit->buyer_id ?? '')==$buyer->id)>{{ $buyer->buyer_name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Style#</label>
            <input type="text" name="style_number" value="{{ old('style_number', $audit->style_number ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Style Name</label>
            <input type="text" name="style_name" value="{{ old('style_name', $audit->style_name ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">PO#</label>
            <input type="text" name="po_number" value="{{ old('po_number', $audit->po_number ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">LOT#</label>
            <input type="text" name="lot_number" value="{{ old('lot_number', $audit->lot_number ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fabric</label>
            <input type="text" name="fabric" value="{{ old('fabric', $audit->fabric ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Colour</label>
            <input type="text" name="colour" value="{{ old('colour', $audit->colour ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">DIA</label>
            <input type="text" name="dia" value="{{ old('dia', $audit->dia ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <h3 class="font-semibold text-gray-800 text-sm mb-3">Lay Details</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Lay Number</label>
            <input type="text" name="lay_number" value="{{ old('lay_number', $audit->lay_number ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Cut Number</label>
            <input type="text" name="cut_number" value="{{ old('cut_number', $audit->cut_number ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Number of Sizes *</label>
            <input type="number" min="1" max="100" name="number_of_sizes" required value="{{ old('number_of_sizes', $audit->number_of_sizes ?? 1) }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Lay Method *</label>
            <select name="lay_method" required class="w-full rounded-md border-gray-300 text-sm">
                <option value="manual" @selected(old('lay_method', $audit->lay_method ?? 'manual')==='manual')>Manual</option>
                <option value="spreader" @selected(old('lay_method', $audit->lay_method ?? '')==='spreader')>Spreader</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Type of Marking</label>
            <input type="text" name="marking_type" value="{{ old('marking_type', $audit->marking_type ?? '') }}" placeholder="Manual / CAD marker" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Required Lay Length (m)</label>
            <input type="number" step="0.01" name="required_lay_length" value="{{ old('required_lay_length', $audit->required_lay_length ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Required Lay Width (m)</label>
            <input type="number" step="0.01" name="required_lay_width" value="{{ old('required_lay_width', $audit->required_lay_width ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Actual Lay Length (m)</label>
            <input type="number" step="0.01" name="actual_lay_length" value="{{ old('actual_lay_length', $audit->actual_lay_length ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Actual Lay Width (m)</label>
            <input type="number" step="0.01" name="actual_lay_width" value="{{ old('actual_lay_width', $audit->actual_lay_width ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Required Plies</label>
            <input type="number" name="required_plies" value="{{ old('required_plies', $audit->required_plies ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Actual Plies</label>
            <input type="number" name="actual_plies" value="{{ old('actual_plies', $audit->actual_plies ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Actual Lay Height (cm)</label>
            <input type="number" step="0.01" name="actual_lay_height" value="{{ old('actual_lay_height', $audit->actual_lay_height ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Total No. of Parts</label>
            <input type="number" name="total_parts" value="{{ old('total_parts', $audit->total_parts ?? '') }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tolerance %</label>
            <input type="number" step="0.01" name="tolerance_pct" value="{{ old('tolerance_pct', $audit->tolerance_pct ?? 5) }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
    </div>
</div>

<div class="flex flex-col-reverse sm:flex-row justify-end gap-2">
    <a href="{{ isset($audit) ? route('admin.cutting.audits.show', $audit) : route('admin.cutting.audits.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Cancel</a>
    <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">{{ isset($audit) ? 'Save changes' : 'Create audit' }}</button>
</div>
