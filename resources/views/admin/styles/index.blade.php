@extends('layouts.app')

@section('title', 'Styles')
@section('header', 'Style Management')

@section('actions')
    @can('create', \App\Models\Style::class)
    <x-form-modal id="add-style" title="Add Style">
        <button class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">+ Add Style</button>
        <x-slot:content>
            <form method="POST" action="{{ route('admin.styles.store') }}">@csrf
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Add New Style</h3>
                <p class="text-xs text-gray-500 mb-4">Enter style details below. Fields marked * are required.</p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Style No <span class="text-red-500">*</span></label>
                        <input type="text" name="style_number" required placeholder="e.g. STY-1005" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Buyer <span class="text-red-500">*</span></label>
                        <select name="buyer_id" required class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select buyer...</option>
                            @foreach(\App\Models\Buyer::where('is_active', true)->orderBy('buyer_name')->get() as $b)<option value="{{ $b->id }}">{{ $b->buyer_name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Order Qty (kg) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="order_quantity" required placeholder="0.00" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Target Date <span class="text-red-500">*</span></label>
                        <input type="date" name="target_date" required value="{{ date('Y-m-d') }}" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fabric Type</label>
                        <input type="text" name="fabric_type" placeholder="e.g. Cotton Fleece" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Color</label>
                        <input type="text" name="color" placeholder="e.g. Navy" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">GSM Target</label>
                        <input type="number" step="0.01" name="gsm_target" placeholder="e.g. 220" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Width Target (inches)</label>
                        <input type="number" step="0.01" name="width_target" placeholder="e.g. 180" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="col-span-2 md:col-span-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                        <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                            @foreach(['planning','in_progress','completed','on_hold'] as $s)<option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 mt-4">
                    <button type="button" @click="open = false" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white text-center hover:bg-blue-700">Save Record</button>
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

{{-- Styles table --}}
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
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
                        <div class="inline-flex items-center gap-1">
                            @can('update', $style)
                            <x-form-modal :id="'edit-'.$style->id" title="Edit Style">
                                <button class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-yellow-700 bg-yellow-50 hover:bg-yellow-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.41-9.41a2 2 0 112.83 2.83L11.83 15H9v-2.83l8.59-8.58z"/></svg>
                                    Edit
                                </button>
                            <x-slot:content>
                                <form method="POST" action="{{ route('admin.styles.update', $style) }}">@csrf @method('PUT')
                                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Edit Style</h3>
                                    <p class="text-xs text-gray-500 mb-4">Update style details below.</p>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Style No <span class="text-red-500">*</span></label>
                                            <input type="text" name="style_number" value="{{ $style->style_number }}" required class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Buyer <span class="text-red-500">*</span></label>
                                            <select name="buyer_id" required class="w-full rounded-md border-gray-300 text-sm">
                                                <option value="">Select buyer...</option>
                                                @foreach(\App\Models\Buyer::orderBy('buyer_name')->get() as $b)<option value="{{ $b->id }}" @selected($style->buyer_id==$b->id)>{{ $b->buyer_name }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Order Qty <span class="text-red-500">*</span></label>
                                            <input type="number" step="0.01" name="order_quantity" value="{{ $style->order_quantity }}" required class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Target Date <span class="text-red-500">*</span></label>
                                            <input type="date" name="target_date" value="{{ $style->target_date?->format('Y-m-d') }}" required class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Fabric Type</label>
                                            <input type="text" name="fabric_type" value="{{ $style->fabric_type }}" placeholder="e.g. Cotton Fleece" class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Color</label>
                                            <input type="text" name="color" value="{{ $style->color }}" placeholder="e.g. Navy" class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">GSM Target</label>
                                            <input type="number" step="0.01" name="gsm_target" value="{{ $style->gsm_target }}" placeholder="e.g. 220" class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Width Target (in)</label>
                                            <input type="number" step="0.01" name="width_target" value="{{ $style->width_target }}" placeholder="e.g. 180" class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div class="col-span-2 md:col-span-4">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                            <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                                                @foreach(['planning','in_progress','completed','on_hold'] as $s)<option value="{{ $s }}" @selected($style->status==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 mt-4">
                                        <button type="button" @click="open = false" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Cancel</button>
                                        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white text-center hover:bg-blue-700">Save Record</button>
                                    </div>
                                </form>
                            </x-slot:content>
                        </x-form-modal>
                        @endcan
                        @can('delete', $style)
                        <x-confirm-modal :id="'del-'.$style->id" title="Delete Style?" method="DELETE" :action="route('admin.styles.destroy', $style)" confirm-text="Delete">
                            <button class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-2-1.86L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                                Delete
                            </button>
                            <x-slot:content>Delete style <strong>{{ $style->style_number }}</strong>?</x-slot:content>
                        </x-confirm-modal>
                        @endcan
                        </div>
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
@endsection
