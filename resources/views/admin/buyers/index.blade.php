@extends('layouts.app')

@section('title', 'Buyers')
@section('header', 'Buyer Management')

@section('actions')
    @can('create', \App\Models\Buyer::class)
    <x-form-modal id="add-buyer" title="Add Buyer">
        <button class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Add Buyer</button>
        <x-slot:content>
            <form method="POST" action="{{ route('admin.buyers.store') }}">@csrf
                <div class="space-y-3">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Buyer Name *</label><input type="text" name="buyer_name" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Contact Person</label><input type="text" name="contact_person" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Email</label><input type="email" name="email" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Phone</label><input type="text" name="phone" class="w-full rounded-md border-gray-300 text-sm"></div>
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
                    <th class="px-3 py-2 text-left">Buyer</th><th class="px-3 py-2 text-left">Contact</th>
                    <th class="px-3 py-2 text-left">Email</th><th class="px-3 py-2 text-left">Phone</th>
                    <th class="px-3 py-2 text-left">Active</th><th class="px-3 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($buyers as $buyer)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $buyer->buyer_name }}</td>
                    <td class="px-3 py-2">{{ $buyer->contact_person ?? '-' }}</td>
                    <td class="px-3 py-2 text-xs">{{ $buyer->email ?? '-' }}</td>
                    <td class="px-3 py-2 text-xs">{{ $buyer->phone ?? '-' }}</td>
                    <td class="px-3 py-2">
                        @can('update', $buyer)
                        <button onclick="toggleBuyer(this, {{ $buyer->id }})" class="relative inline-flex h-5 w-10 items-center rounded-full transition {{ $buyer->is_active ? 'bg-green-500' : 'bg-gray-300' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $buyer->is_active ? 'translate-x-5' : 'translate-x-1' }}"></span>
                        </button>
                        @else <span class="text-xs">{{ $buyer->is_active ? 'Yes':'No' }}</span> @endcan
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        @can('update', $buyer)
                        <x-form-modal :id="'edit-'.$buyer->id" title="Edit Buyer">
                            <button class="text-yellow-600 hover:underline text-xs">Edit</button>
                            <x-slot:content>
                                <form method="POST" action="{{ route('admin.buyers.update', $buyer) }}">@csrf @method('PUT')
                                    <div class="space-y-3">
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Buyer Name *</label><input type="text" name="buyer_name" value="{{ $buyer->buyer_name }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Contact Person</label><input type="text" name="contact_person" value="{{ $buyer->contact_person }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Email</label><input type="email" name="email" value="{{ $buyer->email }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Phone</label><input type="text" name="phone" value="{{ $buyer->phone }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                    </div>
                                    <div class="mt-4 flex justify-end gap-2">
                                        <button type="button" @click="open = false" class="px-3 py-1.5 text-sm border rounded-md">Cancel</button>
                                        <button type="submit" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md">Save</button>
                                    </div>
                                </form>
                            </x-slot:content>
                        </x-form-modal>
                        @endcan
                        @can('delete', $buyer)
                        <x-confirm-modal :id="'del-'.$buyer->id" title="Delete Buyer?" method="DELETE" :action="route('admin.buyers.destroy', $buyer)" confirm-text="Delete">
                            <button class="text-red-600 hover:underline text-xs ml-2">Delete</button>
                            <x-slot:content>Delete <strong>{{ $buyer->buyer_name }}</strong>? This will fail if styles are linked.</x-slot:content>
                        </x-confirm-modal>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400">No buyers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $buyers->withQueryString()->links() }}</div>
</div>

@push('scripts')
<script>
async function toggleBuyer(btn, id) {
    const res = await fetch('{{ route("admin.buyers.index") }}/' + id + '/toggle-active', { method:'PATCH', headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept':'application/json' } });
    const data = await res.json();
    if (data.success) { btn.classList.toggle('bg-green-500', data.is_active); btn.classList.toggle('bg-gray-300', !data.is_active); btn.querySelector('span').classList.toggle('translate-x-5', data.is_active); btn.querySelector('span').classList.toggle('translate-x-1', !data.is_active); }
}
</script>
@endpush
@endsection
