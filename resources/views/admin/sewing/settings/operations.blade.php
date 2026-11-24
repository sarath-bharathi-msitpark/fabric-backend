@extends('layouts.app')

@section('title', 'Sewing Operations')
@section('header', 'Sewing Operations Catalogue')

@section('actions')
<x-form-modal id="add-operation" title="Add Operation">
    <button class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">+ Add Operation</button>
    <x-slot:content>
        <form method="POST" action="{{ route('admin.sewing.settings.operations.store') }}">@csrf
            <div class="space-y-3">
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Operation Name *</label><input type="text" name="name" required class="w-full rounded-md border-gray-300 text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Default Machine</label>
                    <select name="default_machine_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">—</option>
                        @foreach($machines as $machine)<option value="{{ $machine->id }}">{{ $machine->machine_no }}{{ $machine->type ? ' — '.$machine->type : '' }}</option>@endforeach
                    </select>
                </div>
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
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs"><tr><th class="px-3 py-2 text-left">Operation</th><th class="px-3 py-2 text-left">Default Machine</th><th class="px-3 py-2 text-left">Active</th><th class="px-3 py-2 text-left">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($operations as $operation)
                <tr>
                    <td class="px-3 py-2 font-medium">{{ $operation->name }}</td>
                    <td class="px-3 py-2">{{ $operation->defaultMachine->machine_no ?? '—' }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$operation->is_active ? 'approved' : 'pending'" :label="$operation->is_active ? 'Active' : 'Inactive'" /></td>
                    <td class="px-3 py-2">
                        <div class="inline-flex items-center gap-1">
                            <x-form-modal :id="'edit-operation-'.$operation->id" title="Edit Operation">
                                <button class="px-2.5 py-1.5 text-xs rounded-md bg-yellow-50 text-yellow-700 hover:bg-yellow-100">Edit</button>
                                <x-slot:content>
                                    <form method="POST" action="{{ route('admin.sewing.settings.operations.update', $operation) }}">@csrf @method('PUT')
                                        <div class="space-y-3">
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Operation Name *</label><input type="text" name="name" value="{{ $operation->name }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Default Machine</label>
                                                <select name="default_machine_id" class="w-full rounded-md border-gray-300 text-sm">
                                                    <option value="">—</option>
                                                    @foreach($machines as $machine)<option value="{{ $machine->id }}" @selected($operation->default_machine_id === $machine->id)>{{ $machine->machine_no }}{{ $machine->type ? ' — '.$machine->type : '' }}</option>@endforeach
                                                </select>
                                            </div>
                                            <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($operation->is_active)> Active</label>
                                        </div>
                                        <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                                            <button type="button" @click="open=false" class="px-4 py-2 text-sm rounded-md border border-gray-300">Cancel</button>
                                            <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white">Save</button>
                                        </div>
                                    </form>
                                </x-slot:content>
                            </x-form-modal>
                            @if($operation->is_active)
                            <form method="POST" action="{{ route('admin.sewing.settings.operations.destroy', $operation) }}" onsubmit="return confirm('Deactivate this operation?')">@csrf @method('DELETE')
                                <button class="px-2.5 py-1.5 text-xs rounded-md bg-red-50 text-red-700 hover:bg-red-100">Deactivate</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-3 py-8 text-center text-gray-400">No operations.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $operations->links() }}</div>
</div>
@endsection
