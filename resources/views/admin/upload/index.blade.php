@extends('layouts.app')

@section('title', 'Data Upload')
@section('header', 'Data Upload')

@section('actions')
    <a href="{{ route('admin.upload.template') }}" class="px-3 py-1.5 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Download Template</a>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6" x-data="{ tab: 'excel' }">
        {{-- Tab buttons --}}
        <div class="flex border-b border-gray-200 mb-4">
            <button @click="tab = 'excel'" :class="tab === 'excel' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition">
                Excel Upload
            </button>
            <button @click="tab = 'manual'" :class="tab === 'manual' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition">
                Manual Entry
            </button>
        </div>

        {{-- Excel Upload Tab --}}
        <div x-show="tab === 'excel'">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Upload Excel File</h3>
        <form id="uploadForm" action="{{ route('admin.upload.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Upload Type</label>
                <select name="upload_type" id="upload_type" class="rounded-md border-gray-300 w-full text-sm">
                    <option value="new_records">New Records</option>
                    <option value="daily_update">Daily Update</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Choose File (.xlsx, .xls, max 10MB)</label>
                <input type="file" name="file" id="fileInput" accept=".xlsx,.xls" class="block w-full text-sm text-gray-600 border border-gray-300 rounded-md p-2" required>
                <p id="fileError" class="text-red-600 text-xs mt-1 hidden"></p>
            </div>
            <div class="flex gap-2">
                <button type="button" id="validateBtn" class="px-4 py-2 text-sm rounded-md bg-yellow-600 text-white hover:bg-yellow-700">Validate</button>
                <button type="submit" id="importBtn" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Import</button>
            </div>
        </form>

        @if(isset($validation))
        <div class="mt-6 border-t border-gray-200 pt-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-700">Validation Preview</h4>
                <div class="text-xs space-x-3">
                    <span class="text-green-700">{{ $validation['valid_count'] }} valid</span>
                    <span class="text-red-700">{{ $validation['error_count'] }} errors</span>
                </div>
            </div>
            <div class="overflow-x-auto max-h-96 overflow-y-auto border border-gray-200 rounded-md">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-left">Row</th>
                            <th class="px-2 py-2 text-left">Lot No</th>
                            <th class="px-2 py-2 text-left">Buyer</th>
                            <th class="px-2 py-2 text-left">Style</th>
                            <th class="px-2 py-2 text-left">Supplier</th>
                            <th class="px-2 py-2 text-left">Ordered</th>
                            <th class="px-2 py-2 text-left">Received</th>
                            <th class="px-2 py-2 text-left">Status</th>
                            <th class="px-2 py-2 text-left">Errors</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($validation['rows'] as $row)
                        <tr class="{{ $row['valid'] ? 'bg-green-50' : 'bg-red-50' }}">
                            <td class="px-2 py-1">{{ $row['row'] }}</td>
                            <td class="px-2 py-1">{{ $row['data']['lot_no'] ?? '' }}</td>
                            <td class="px-2 py-1">{{ $row['data']['buyer'] ?? '' }}</td>
                            <td class="px-2 py-1">{{ $row['data']['style'] ?? '' }}</td>
                            <td class="px-2 py-1">{{ $row['data']['supplier'] ?? '' }}</td>
                            <td class="px-2 py-1">{{ $row['data']['ordered_kg'] ?? '' }}</td>
                            <td class="px-2 py-1">{{ $row['data']['received_kg'] ?? '' }}</td>
                            <td class="px-2 py-1">{{ $row['valid'] ? '✓ Valid' : '✗ Error' }}</td>
                            <td class="px-2 py-1 text-red-700">{{ implode('; ', $row['errors']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($validation['valid_count'] > 0)
            <div class="mt-3 p-3 bg-blue-50 rounded-md text-sm text-blue-800">
                Ready to import <strong>{{ $validation['valid_count'] }}</strong> valid rows. {{ $validation['error_count'] }} error rows will be skipped. Click <strong>Import</strong> above.
            </div>
            @endif
        </div>
        @endif
        </div>{{-- End Excel Upload Tab --}}

        {{-- Manual Entry Tab --}}
        <div x-show="tab === 'manual'" x-data="{ showInspection: false }">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Manual Data Entry</h3>
            <p class="text-xs text-gray-500 mb-4">Enter fabric record details directly. For new lots, select "New Records". To update an existing lot, select "Daily Update" and enter the same Lot No.</p>

            <form action="{{ route('admin.upload.manual') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Entry Type</label>
                    <select name="upload_type" class="rounded-md border-gray-300 w-full text-sm">
                        <option value="new_records">New Records (create new lot)</option>
                        <option value="daily_update">Daily Update (update existing lot)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="record_date" value="{{ old('record_date', date('Y-m-d')) }}" class="w-full rounded-md border-gray-300 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Lot No <span class="text-red-500">*</span></label>
                        <input type="text" name="lot_no" value="{{ old('lot_no') }}" placeholder="e.g. LOT-2401-005" class="w-full rounded-md border-gray-300 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Buyer <span class="text-red-500">*</span></label>
                        <select name="buyer_id" class="w-full rounded-md border-gray-300 text-sm" required>
                            <option value="">Select buyer...</option>
                            @foreach($buyers as $b)<option value="{{ $b->id }}" @selected(old('buyer_id')==$b->id)>{{ $b->buyer_name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Style <span class="text-red-500">*</span></label>
                        <select name="style_id" class="w-full rounded-md border-gray-300 text-sm" required>
                            <option value="">Select style...</option>
                            @foreach($styles as $s)<option value="{{ $s->id }}" @selected(old('style_id')==$s->id)>{{ $s->style_number }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Supplier <span class="text-red-500">*</span></label>
                        <select name="supplier_id" class="w-full rounded-md border-gray-300 text-sm" required>
                            <option value="">Select supplier...</option>
                            @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->supplier_name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fabric Type <span class="text-red-500">*</span></label>
                        <input type="text" name="fabric_type" value="{{ old('fabric_type') }}" list="fabricTypesList" placeholder="e.g. Cotton Fleece" class="w-full rounded-md border-gray-300 text-sm" required>
                        <datalist id="fabricTypesList">
                            @foreach($fabricTypes as $ft)<option value="{{ $ft }}">@endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Color <span class="text-red-500">*</span></label>
                        <input type="text" name="color" value="{{ old('color') }}" placeholder="e.g. Black" class="w-full rounded-md border-gray-300 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Ordered (kg) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="ordered_kg" value="{{ old('ordered_kg') }}" placeholder="0.00" class="w-full rounded-md border-gray-300 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Received (kg) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="received_kg" value="{{ old('received_kg') }}" placeholder="0.00" class="w-full rounded-md border-gray-300 text-sm" required>
                    </div>
                </div>

                {{-- Optional inspection section --}}
                <div class="border-t border-gray-200 pt-4">
                    <button type="button" @click="showInspection = !showInspection" class="text-xs font-medium text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                        <svg class="w-3 h-3 transition" :class="showInspection ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        Inspection Details (optional)
                    </button>

                    <div x-show="showInspection" x-transition class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Inspected (kg)</label>
                            <input type="number" step="0.01" name="inspected_kg" value="{{ old('inspected_kg') }}" placeholder="0.00" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Approved (kg)</label>
                            <input type="number" step="0.01" name="approved_kg" value="{{ old('approved_kg') }}" placeholder="0.00" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Rejected (kg)</label>
                            <input type="number" step="0.01" name="rejected_kg" value="{{ old('rejected_kg') }}" placeholder="0.00" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">GSM Actual</label>
                            <input type="number" step="0.01" name="gsm_actual" value="{{ old('gsm_actual') }}" placeholder="e.g. 235" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Width Actual (inches)</label>
                            <input type="number" step="0.01" name="width_actual" value="{{ old('width_actual') }}" placeholder="e.g. 76" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Shade Status</label>
                            <select name="shade_status" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="pending">Pending</option>
                                <option value="approved" @selected(old('shade_status')=='approved')>Approved</option>
                                <option value="rejected" @selected(old('shade_status')=='rejected')>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Inspection Date</label>
                            <input type="date" name="inspection_date" value="{{ old('inspection_date') }}" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2" x-show="showInspection">For detailed roll-by-roll inspection with 4-point scoring, save this record then use the QC Inspection page.</p>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save Record</button>
                </div>
            </form>
        </div>{{-- End Manual Entry Tab --}}
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Template Format</h3>
        <p class="text-xs text-gray-500 mb-2">Required columns (in order):</p>
        <ol class="text-xs text-gray-600 space-y-1 list-decimal list-inside">
            <li>Date (YYYY-MM-DD)</li><li>Buyer</li><li>Style</li><li>Supplier</li>
            <li>Lot No</li><li>Fabric Type</li><li>Color</li>
            <li>Ordered Kg</li><li>Received Kg</li><li>Inspected Kg</li>
            <li>Approved Kg</li><li>Rejected Kg</li><li>GSM</li><li>Width</li>
            <li>Pass %</li><li>Defect Type</li><li>Defect Count</li><li>Severity</li>
        </ol>
        <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
            <p><strong>Validation rules:</strong></p>
            <ul class="list-disc list-inside mt-1 space-y-0.5">
                <li>Date must be YYYY-MM-DD</li><li>Numeric fields: no commas/currency</li>
                <li>Lot No unique (New) or existing (Update)</li>
            </ul>
        </div>
    </div>
</div>

<div class="mt-8 bg-white rounded-lg shadow-sm p-4">
    <h3 class="text-sm font-semibold text-gray-700 mb-3">Upload History</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">File Name</th><th class="px-3 py-2 text-left">Type</th>
                    <th class="px-3 py-2 text-left">Uploaded By</th><th class="px-3 py-2 text-left">Date</th>
                    <th class="px-3 py-2 text-left">Total</th><th class="px-3 py-2 text-left">Success</th>
                    <th class="px-3 py-2 text-left">Errors</th><th class="px-3 py-2 text-left">Status</th>
                    <th class="px-3 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($batches as $batch)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $batch->file_name }}</td>
                    <td class="px-3 py-2 text-xs">{{ str_replace('_', ' ', $batch->upload_type) }}</td>
                    <td class="px-3 py-2">{{ $batch->uploader?->name }}</td>
                    <td class="px-3 py-2 text-xs">{{ $batch->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-3 py-2">{{ $batch->total_rows }}</td>
                    <td class="px-3 py-2 text-green-700">{{ $batch->success_rows }}</td>
                    <td class="px-3 py-2 text-red-700">{{ $batch->error_rows }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$batch->status" /></td>
                    <td class="px-3 py-2">
                        @if($batch->error_log)
                        <x-form-modal :id="'errors-'.$batch->id" title="Error Log - {{ $batch->file_name }}">
                            <button class="text-blue-600 hover:underline text-xs">View Errors</button>
                            <x-slot:content>
                                <div class="max-h-80 overflow-y-auto text-xs space-y-2">
                                    @foreach($batch->error_log as $err)
                                    <div class="p-2 bg-red-50 rounded">
                                        <div class="font-medium text-red-800">Row {{ $err['row'] ?? '?' }} — Lot: {{ $err['lot_no'] ?? '' }}</div>
                                        <ul class="list-disc list-inside text-red-700 mt-1">
                                            @foreach(($err['errors'] ?? []) as $e)<li>{{ $e }}</li>@endforeach
                                        </ul>
                                    </div>
                                    @endforeach
                                </div>
                            </x-slot:content>
                        </x-form-modal>
                        @else
                        <span class="text-xs text-gray-400">No errors</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-3 py-6 text-center text-gray-400">No uploads yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $batches->withQueryString()->links() }}
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const validateBtn = document.getElementById('validateBtn');
    const importBtn = document.getElementById('importBtn');
    const fileError = document.getElementById('fileError');
    const form = document.getElementById('uploadForm');

    fileInput.addEventListener('change', function() {
        fileError.classList.add('hidden');
        const file = this.files[0];
        if (file) {
            const ext = file.name.split('.').pop().toLowerCase();
            if (!['xlsx','xls'].includes(ext)) {
                fileError.textContent = 'Only .xlsx and .xls files are allowed.';
                fileError.classList.remove('hidden');
                this.value = '';
            }
            if (file.size > 10 * 1024 * 1024) {
                fileError.textContent = 'File must be under 10MB.';
                fileError.classList.remove('hidden');
                this.value = '';
            }
        }
    });

    validateBtn.addEventListener('click', function() {
        if (!fileInput.files[0]) {
            fileError.textContent = 'Please choose a file first.';
            fileError.classList.remove('hidden');
            return;
        }
        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('upload_type', document.getElementById('upload_type').value);
        formData.append('_token', document.querySelector('meta[name=csrf-token]').content);

        form.action = '{{ route('admin.upload.validate') }}';
        const originalAction = form.getAttribute('action');
        fetch('{{ route('admin.upload.validate') }}', {
            method: 'POST', body: formData,
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'text/html' }
        }).then(r => r.text()).then(html => {
            document.open(); document.write(html); document.close();
        }).catch(err => alert('Validation failed: ' + err));
    });

    importBtn.addEventListener('click', function(e) {
        if (!fileInput.files[0]) { e.preventDefault(); return; }
        if (!confirm('Import the validated rows? Error rows will be skipped.')) { e.preventDefault(); }
    });
});
</script>
@endpush
@endsection
