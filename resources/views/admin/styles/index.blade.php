@extends('layouts.app')

@section('title', 'Styles')
@section('header', 'Style Management')

@section('actions')
    <a href="{{ route('admin.styles.template') }}" class="px-3 py-1.5 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Download Template</a>
    @can('create', \App\Models\Style::class)
    <x-form-modal id="add-style" title="Add Style">
        <button class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Add Style</button>
        <x-slot:content>
            <form method="POST" action="{{ route('admin.styles.store') }}">@csrf
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Style No *</label><input type="text" name="style_number" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Buyer *</label>
                        <select name="buyer_id" required class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select...</option>
                            @foreach(\App\Models\Buyer::orderBy('buyer_name')->get() as $b)<option value="{{ $b->id }}">{{ $b->buyer_name }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Order Qty (kg) *</label><input type="number" step="0.01" name="order_quantity" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Target Date *</label><input type="date" name="target_date" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Fabric Type</label><input type="text" name="fabric_type" placeholder="e.g. Cotton Fleece" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Color</label><input type="text" name="color" placeholder="e.g. Navy" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">GSM Target</label><input type="number" step="0.01" name="gsm_target" placeholder="e.g. 220" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Width Target (inches)</label><input type="number" step="0.01" name="width_target" placeholder="e.g. 180" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div class="col-span-2"><label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                        <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                            @foreach(['planning','in_progress','completed','on_hold'] as $s)<option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="open = false" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save</button>
                </div>
            </form>
        </x-slot:content>
    </x-form-modal>
    @endcan
@endsection

@section('content')

{{-- Import result flash --}}
@if(session('import_result'))
<div class="bg-white rounded-lg shadow-sm p-4 mb-4 border-l-4 {{ session('import_result.success') > 0 ? 'border-green-500' : 'border-red-500' }}">
    <div class="flex items-center justify-between">
        <div class="text-sm">
            <span class="font-semibold text-gray-800">Style Import Result:</span>
            <span class="text-green-700 ml-2">{{ session('import_result.success') }} imported</span>
            @if(count(session('import_result.errors')) > 0)<span class="text-red-700 ml-2">{{ count(session('import_result.errors')) }} errors</span>@endif
        </div>
    </div>
    @if(count(session('import_result.errors')) > 0)
    <div class="mt-2 text-xs text-red-600">
        @foreach(session('import_result.errors') as $err)<div>Row {{ $err['row'] }} — {{ $err['style'] }}: {{ implode(', ', $err['errors']) }}</div>@endforeach
    </div>
    @endif
</div>
@endif

{{-- Upload form + table --}}
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
    {{-- Upload form --}}
    <div class="bg-white rounded-lg shadow-sm p-6 lg:col-span-1">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Upload Styles (Excel)</h3>
        <p class="text-xs text-gray-500 mb-3">Upload an Excel file with style data. Use the template for the correct format.</p>
        <form action="{{ route('admin.styles.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Choose File (.xlsx, .xls)</label>
                <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full text-xs text-gray-600 border border-gray-300 rounded-md p-2">
            </div>
            <button type="submit" class="w-full px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Import Styles</button>
        </form>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500 font-medium mb-1">9 Fields:</p>
            <ol class="text-xs text-gray-400 space-y-0.5 list-decimal list-inside">
                <li>Style Number</li><li>Buyer</li><li>Order Qty (kg)</li>
                <li>Target Date</li><li>Status</li><li>Fabric Type</li>
                <li>Color</li><li>GSM Target</li><li>Width Target</li>
            </ol>
        </div>
    </div>

    {{-- Styles table --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden lg:col-span-3">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-3 py-2 text-left">Style No</th><th class="px-3 py-2 text-left">Buyer</th>
                        <th class="px-3 py-2 text-right">Order Qty</th><th class="px-3 py-2 text-left">Target Date</th>
                        <th class="px-3 py-2 text-left">Fabric Type</th><th class="px-3 py-2 text-left">Color</th>
                        <th class="px-3 py-2 text-right">GSM</th><th class="px-3 py-2 text-right">Width</th>
                        <th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($styles as $style)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-medium text-gray-800">{{ $style->style_number }}</td>
                        <td class="px-3 py-2">{{ $style->buyer?->buyer_name }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float)$style->order_quantity, 2) }}</td>
                        <td class="px-3 py-2 text-xs">{{ $style->target_date?->format('Y-m-d') }}</td>
                        <td class="px-3 py-2 text-xs">{{ $style->fabric_type ?? '—' }}</td>
                        <td class="px-3 py-2 text-xs">{{ $style->color ?? '—' }}</td>
                        <td class="px-3 py-2 text-right text-xs">{{ $style->gsm_target ?? '—' }}</td>
                        <td class="px-3 py-2 text-right text-xs">{{ $style->width_target ?? '—' }}</td>
                        <td class="px-3 py-2"><x-status-badge :status="$style->status" /></td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @can('update', $style)
                            <x-form-modal :id="'edit-'.$style->id" title="Edit Style">
                                <button class="text-yellow-600 hover:underline text-xs">Edit</button>
                                <x-slot:content>
                                    <form method="POST" action="{{ route('admin.styles.update', $style) }}">@csrf @method('PUT')
                                        <div class="grid grid-cols-2 gap-3">
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Style No *</label><input type="text" name="style_number" value="{{ $style->style_number }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Buyer *</label>
                                                <select name="buyer_id" required class="w-full rounded-md border-gray-300 text-sm">
                                                    @foreach(\App\Models\Buyer::orderBy('buyer_name')->get() as $b)<option value="{{ $b->id }}" @selected($style->buyer_id==$b->id)>{{ $b->buyer_name }}</option>@endforeach
                                                </select>
                                            </div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Order Qty *</label><input type="number" step="0.01" name="order_quantity" value="{{ $style->order_quantity }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Target Date *</label><input type="date" name="target_date" value="{{ $style->target_date?->format('Y-m-d') }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Fabric Type</label><input type="text" name="fabric_type" value="{{ $style->fabric_type }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Color</label><input type="text" name="color" value="{{ $style->color }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">GSM Target</label><input type="number" step="0.01" name="gsm_target" value="{{ $style->gsm_target }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Width Target (in)</label><input type="number" step="0.01" name="width_target" value="{{ $style->width_target }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div class="col-span-2"><label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                                <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                                                    @foreach(['planning','in_progress','completed','on_hold'] as $s)<option value="{{ $s }}" @selected($style->status==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex justify-end gap-2">
                                            <button type="button" @click="open = false" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Cancel</button>
                                            <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save</button>
                                        </div>
                                    </form>
                                </x-slot:content>
                            </x-form-modal>
                            @endcan
                            @can('delete', $style)
                            <x-confirm-modal :id="'del-'.$style->id" title="Delete Style?" method="DELETE" :action="route('admin.styles.destroy', $style)" confirm-text="Delete">
                                <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
                                <x-slot:content>Delete style <strong>{{ $style->style_number }}</strong>?</x-slot:content>
                            </x-confirm-modal>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="px-3 py-8 text-center text-gray-400">No styles found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100">{{ $styles->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
