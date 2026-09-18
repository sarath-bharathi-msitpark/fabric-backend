@extends('layouts.app')

@section('title', 'Suppliers')
@section('header', 'Supplier Management')

@section('actions')
    @can('create', \App\Models\Supplier::class)
    <x-form-modal id="add-supplier" title="Add Supplier">
        <button class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">+ Add Supplier</button>
        <x-slot:content>
            <form method="POST" action="{{ route('admin.suppliers.store') }}">@csrf
                <div class="space-y-3">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Supplier Name *</label><input type="text" name="supplier_name" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Mill Code</label><input type="text" name="mill_code" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Contact Person</label><input type="text" name="contact_person" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Phone</label><input type="text" name="phone" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Email</label><input type="email" name="email" class="w-full rounded-md border-gray-300 text-sm"></div>
                </div>
                <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                    <button type="button" @click="open = false" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white text-center hover:bg-blue-700">Save</button>
                </div>
            </form>
        </x-slot:content>
    </x-form-modal>
    @endcan
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm p-4 mb-4 print:hidden">
    <form method="GET" action="{{ route('admin.suppliers.index') }}" class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="col-span-2 md:col-span-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search supplier / mill / contact..." class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <select name="is_active" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="All">
                <option value="">All</option>
                <option value="1" @selected(request('is_active')==='1')>Active</option>
                <option value="0" @selected(request('is_active')==='0')>Inactive</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Apply</button>
            <a href="{{ route('admin.suppliers.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Reset</a>
        </div>
    </form>
</div>

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
                        <div class="inline-flex items-center gap-1">
                            <a href="{{ route('admin.suppliers.show', $supplier) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.46 12C3.73 7.94 7.52 5 12 5s8.27 2.94 9.54 7c-1.27 4.06-5.06 7-9.54 7s-8.27-2.94-9.54-7z"/></svg>
                                View
                            </a>
                            @can('update', $supplier)
                            <x-form-modal :id="'edit-'.$supplier->id" title="Edit Supplier">
                                <button class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-yellow-700 bg-yellow-50 hover:bg-yellow-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.41-9.41a2 2 0 112.83 2.83L11.83 15H9v-2.83l8.59-8.58z"/></svg>
                                    Edit
                                </button>
                            <x-slot:content>
                                <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">@csrf @method('PUT')
                                    <div class="space-y-3">
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Supplier Name *</label><input type="text" name="supplier_name" value="{{ $supplier->supplier_name }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Mill Code</label><input type="text" name="mill_code" value="{{ $supplier->mill_code }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Contact Person</label><input type="text" name="contact_person" value="{{ $supplier->contact_person }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Phone</label><input type="text" name="phone" value="{{ $supplier->phone }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Email</label><input type="email" name="email" value="{{ $supplier->email }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                    </div>
                                    <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                                        <button type="button" @click="open = false" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Cancel</button>
                                        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white text-center hover:bg-blue-700">Save</button>
                                    </div>
                                </form>
                            </x-slot:content>
                        </x-form-modal>
                        @endcan
                        </div>
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
