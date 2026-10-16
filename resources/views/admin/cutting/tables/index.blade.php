@extends('layouts.app')

@section('title', 'Cutting Tables')
@section('header', 'Cutting Tables Master')

@section('actions')
<x-form-modal id="add-table" title="Add Cutting Table">
    <button class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">+ Add Table</button>
    <x-slot:content>
        <form method="POST" action="{{ route('admin.cutting.tables.store') }}">@csrf
            <div class="space-y-3">
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Table No *</label><input type="text" name="table_no" required class="w-full rounded-md border-gray-300 text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Name</label><input type="text" name="name" class="w-full rounded-md border-gray-300 text-sm"></div>
            </div>
            <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                <button type="button" @click="open=false" class="px-4 py-2 text-sm rounded-md border border-gray-300">Cancel</button>
                <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white">Save</button>
            </div>
        </form>
    </x-slot:content>
</x-form-modal>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs"><tr><th class="px-3 py-2 text-left">Table No</th><th class="px-3 py-2 text-left">Name</th><th class="px-3 py-2 text-left">Active</th><th class="px-3 py-2 text-left">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tables as $table)
                <tr>
                    <td class="px-3 py-2 font-medium">{{ $table->table_no }}</td>
                    <td class="px-3 py-2">{{ $table->name ?? '—' }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$table->is_active ? 'approved' : 'pending'" :label="$table->is_active ? 'Active' : 'Inactive'" /></td>
                    <td class="px-3 py-2">
                        <div class="inline-flex items-center gap-1">
                            <x-form-modal :id="'edit-table-'.$table->id" title="Edit Cutting Table">
                                <button class="px-2.5 py-1.5 text-xs rounded-md bg-yellow-50 text-yellow-700 hover:bg-yellow-100">Edit</button>
                                <x-slot:content>
                                    <form method="POST" action="{{ route('admin.cutting.tables.update', $table) }}">@csrf @method('PUT')
                                        <div class="space-y-3">
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Table No *</label><input type="text" name="table_no" value="{{ $table->table_no }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Name</label><input type="text" name="name" value="{{ $table->name }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($table->is_active)> Active</label>
                                        </div>
                                        <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                                            <button type="button" @click="open=false" class="px-4 py-2 text-sm rounded-md border border-gray-300">Cancel</button>
                                            <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white">Save</button>
                                        </div>
                                    </form>
                                </x-slot:content>
                            </x-form-modal>
                            @if($table->is_active)
                            <form method="POST" action="{{ route('admin.cutting.tables.destroy', $table) }}" onsubmit="return confirm('Deactivate this table?')">@csrf @method('DELETE')
                                <button class="px-2.5 py-1.5 text-xs rounded-md bg-red-50 text-red-700 hover:bg-red-100">Deactivate</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-3 py-8 text-center text-gray-400">No cutting tables.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $tables->links() }}</div>
</div>
@endsection
