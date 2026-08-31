@extends('layouts.app')

@section('title', 'Styles')
@section('header', 'Style Management')

@section('actions')
    @can('create', \App\Models\Style::class)
    <x-form-modal id="add-style" title="Add Style">
        <button class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Add Style</button>
        <x-slot:content>
            <form method="POST" action="{{ route('admin.styles.store') }}">@csrf
                <div class="space-y-3">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Style No *</label><input type="text" name="style_number" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Buyer *</label>
                        <select name="buyer_id" required class="w-full rounded-md border-gray-300 text-sm">
                            @foreach(\App\Models\Buyer::orderBy('buyer_name')->get() as $b)<option value="{{ $b->id }}">{{ $b->buyer_name }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Order Qty (kg) *</label><input type="number" step="0.01" name="order_quantity" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Target Date *</label><input type="date" name="target_date" required class="w-full rounded-md border-gray-300 text-sm"></div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="open = false" class="px-3 py-1.5 text-sm border rounded-md">Cancel</button>
                    <button type="submit" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md">Save</button>
                </div>
            </form>
        </x-slot:content>
    </x-form-modal>
    @endcan
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">Style No</th><th class="px-3 py-2 text-left">Buyer</th>
                    <th class="px-3 py-2 text-right">Order Qty</th><th class="px-3 py-2 text-left">Target Date</th>
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
                    <td class="px-3 py-2"><x-status-badge :status="$style->status" /></td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        @can('update', $style)
                        <x-form-modal :id="'edit-'.$style->id" title="Edit Style">
                            <button class="text-yellow-600 hover:underline text-xs">Edit</button>
                            <x-slot:content>
                                <form method="POST" action="{{ route('admin.styles.update', $style) }}">@csrf @method('PUT')
                                    <div class="space-y-3">
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Style No *</label><input type="text" name="style_number" value="{{ $style->style_number }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Buyer *</label>
                                            <select name="buyer_id" required class="w-full rounded-md border-gray-300 text-sm">
                                                @foreach(\App\Models\Buyer::orderBy('buyer_name')->get() as $b)<option value="{{ $b->id }}" @selected($style->buyer_id==$b->id)>{{ $b->buyer_name }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Order Qty *</label><input type="number" step="0.01" name="order_quantity" value="{{ $style->order_quantity }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Target Date *</label><input type="date" name="target_date" value="{{ $style->target_date?->format('Y-m-d') }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                            <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                                                @foreach(['planning','in_progress','completed','on_hold'] as $s)<option value="{{ $s }}" @selected($style->status==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-end gap-2">
                                        <button type="button" @click="open = false" class="px-3 py-1.5 text-sm border rounded-md">Cancel</button>
                                        <button type="submit" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md">Save</button>
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
                <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400">No styles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $styles->withQueryString()->links() }}</div>
</div>
@endsection
