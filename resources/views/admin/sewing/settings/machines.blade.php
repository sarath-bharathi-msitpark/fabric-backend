@extends('layouts.app')

@section('title', 'Sewing Machines')
@section('header', 'Sewing Machines Master')

@section('actions')
<x-form-modal id="add-machine" title="Add Machine">
    <button class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">+ Add Machine</button>
    <x-slot:content>
        <form method="POST" action="{{ route('admin.sewing.settings.machines.store') }}">@csrf
            <div class="space-y-3">
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Machine No *</label><input type="text" name="machine_no" required class="w-full rounded-md border-gray-300 text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Type</label><input type="text" name="type" class="w-full rounded-md border-gray-300 text-sm"></div>
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
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs"><tr><th class="px-3 py-2 text-left">Machine No</th><th class="px-3 py-2 text-left">Type</th><th class="px-3 py-2 text-left">Active</th><th class="px-3 py-2 text-left">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($machines as $machine)
                <tr>
                    <td class="px-3 py-2 font-medium">{{ $machine->machine_no }}</td>
                    <td class="px-3 py-2">{{ $machine->type ?? '—' }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$machine->is_active ? 'approved' : 'pending'" :label="$machine->is_active ? 'Active' : 'Inactive'" /></td>
                    <td class="px-3 py-2">
                        <div class="inline-flex items-center gap-1">
                            <x-form-modal :id="'edit-machine-'.$machine->id" title="Edit Machine">
                                <button class="px-2.5 py-1.5 text-xs rounded-md bg-yellow-50 text-yellow-700 hover:bg-yellow-100">Edit</button>
                                <x-slot:content>
                                    <form method="POST" action="{{ route('admin.sewing.settings.machines.update', $machine) }}">@csrf @method('PUT')
                                        <div class="space-y-3">
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Machine No *</label><input type="text" name="machine_no" value="{{ $machine->machine_no }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Type</label><input type="text" name="type" value="{{ $machine->type }}" class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($machine->is_active)> Active</label>
                                        </div>
                                        <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                                            <button type="button" @click="open=false" class="px-4 py-2 text-sm rounded-md border border-gray-300">Cancel</button>
                                            <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white">Save</button>
                                        </div>
                                    </form>
                                </x-slot:content>
                            </x-form-modal>
                            @if($machine->is_active)
                            <form method="POST" action="{{ route('admin.sewing.settings.machines.destroy', $machine) }}" onsubmit="return confirm('Deactivate this machine?')">@csrf @method('DELETE')
                                <button class="px-2.5 py-1.5 text-xs rounded-md bg-red-50 text-red-700 hover:bg-red-100">Deactivate</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-3 py-8 text-center text-gray-400">No machines.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $machines->links() }}</div>
</div>
@endsection
