@extends('layouts.app')

@section('title', 'Suppliers')
@section('header', 'Supplier Management')

@section('actions')
    @can('create', \App\Models\Supplier::class)
    <x-form-modal id="add-supplier" title="Add Supplier">
        <button class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Add Supplier</button>
        <x-slot:content>
            <form method="POST" action="{{ route('admin.suppliers.store') }}">@csrf
                <div class="space-y-3">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Supplier Name *</label><input type="text" name="supplier_name" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Mill Code</label><input type="text" name="mill_code" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Contact Person</label><input type="text" name="contact_person" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Phone</label><input type="text" name="phone" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Email</label><input type="email" name="email" class="w-full rounded-md border-gray-300 text-sm"></div>
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
                    <th class="px-3 py-2 text-left">Supplier</th><th class="px-3 py-2 text-left">Mill Code</th>
                    <th class="px-3 py-2 text-right">On-Time %</th><th class="px-3 py-2 text-right">Quality %</th>
                    <th class="px-3 py-2 text-left">Rating</th><th class="px-3 py-2 text-left">Active</th>
                    <th class="px-3 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($suppliers as $supplier)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $supplier->supplier_name }}</td>
                    <td class="px-3 py-2 text-xs">{{ $supplier->mill_code ?? '-' }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format((float)$supplier->on_time_pct, 2) }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format((float)$supplier->quality_pct, 2) }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$supplier->rating" /></td>
                    <td class="px-3 py-2">
                        @can('update', $supplier)
                        <button onclick="toggleActive(this, {{ $supplier->id }})" class="relative inline-flex h-5 w-10 items-center rounded-full transition {{ $supplier->is_active ? 'bg-green-500' : 'bg-gray-300' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $supplier->is_active ? 'translate-x-5' : 'translate-x-1' }}"></span>
                        </button>
                        @else
                        <span class="text-xs">{{ $supplier->is_active ? 'Yes' : 'No' }}</span>
                        @endcan
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        <a href="{{ route('admin.suppliers.show', $supplier) }}" class="text-blue-600 hover:underline text-xs">View Performance</a>
                        @can('update', $supplier)
                        <x-form-modal :id="'edit-'.$supplier->id" title="Edit Supplier">
                            <button class="text-yellow-600 hover:underline text-xs ml-2">Edit</button>
                            <x-slot:content>
                                <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">@csrf @method('PUT')
                                    <div class="space-y-3">
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Supplier Name *</label><input type="text" name="supplier_name" value="{{ $supplier->supplier_name }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Mill Code</label><input type="text" name="mill_code" value="{{ $supplier->mill_code }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Contact Person</label><input type="text" name="contact_person" value="{{ $supplier->contact_person }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Phone</label><input type="text" name="phone" value="{{ $supplier->phone }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Email</label><input type="email" name="email" value="{{ $supplier->email }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                    </div>
                                    <div class="mt-4 flex justify-end gap-2">
                                        <button type="button" @click="open = false" class="px-3 py-1.5 text-sm border rounded-md">Cancel</button>
                                        <button type="submit" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md">Save</button>
                                    </div>
                                </form>
                            </x-slot:content>
                        </x-form-modal>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-3 py-8 text-center text-gray-400">No suppliers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $suppliers->withQueryString()->links() }}</div>
</div>

@push('scripts')
<script>
async function toggleActive(btn, id) {
    const res = await fetch('{{ route("admin.suppliers.index") }}/' + id + '/toggle-active', {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept':'application/json' }
    });
    const data = await res.json();
    if (data.success) {
        btn.classList.toggle('bg-green-500', data.is_active);
        btn.classList.toggle('bg-gray-300', !data.is_active);
        btn.querySelector('span').classList.toggle('translate-x-5', data.is_active);
        btn.querySelector('span').classList.toggle('translate-x-1', !data.is_active);
    }
}
</script>
@endpush
@endsection
