@extends('layouts.app')

@section('title', 'Defect Master (DCL)')
@section('header', 'Defect Master (DCL with CAPA)')

@section('actions')
<x-form-modal id="add-defect" title="Add Defect Master Row">
    <button class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">+ Add Defect</button>
    <x-slot:content>
        <form method="POST" action="{{ route('admin.sewing.settings.defects.store') }}">@csrf
            @include('admin.sewing.settings._defect_fields', ['defect' => null])
            <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                <button type="button" @click="open=false" class="px-4 py-2 text-sm rounded-md border border-gray-300">Cancel</button>
                <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white">Save</button>
            </div>
        </form>
    </x-slot:content>
</x-form-modal>
@endsection

@section('content')
<form method="GET" action="{{ route('admin.sewing.settings.defects.index') }}" class="mb-4 flex items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Defect type</label>
        <select name="type" class="rounded-md border-gray-300 text-sm">
            <option value="">All types</option>
            @foreach($types as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>@endforeach
        </select>
    </div>
    <button class="px-4 py-2 text-sm rounded-md bg-slate-700 text-white">Filter</button>
    <a href="{{ route('admin.sewing.settings.defects.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300">Reset</a>
</form>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs"><tr><th class="px-3 py-2 text-left">Type</th><th class="px-3 py-2 text-left">CAPA</th><th class="px-3 py-2 text-left">Defect</th><th class="px-3 py-2 text-left">Root cause</th><th class="px-3 py-2 text-left">Active</th><th class="px-3 py-2 text-left">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($defects as $defect)
                <tr>
                    <td class="px-3 py-2">{{ $types[$defect->defect_type] ?? $defect->defect_type }}</td>
                    <td class="px-3 py-2 font-mono font-medium">{{ $defect->capa_code }}</td>
                    <td class="px-3 py-2">{{ $defect->defect_name }}</td>
                    <td class="px-3 py-2 text-gray-600">{{ \Illuminate\Support\Str::limit($defect->root_cause, 60) }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$defect->is_active ? 'approved' : 'pending'" :label="$defect->is_active ? 'Active' : 'Inactive'" /></td>
                    <td class="px-3 py-2">
                        <div class="inline-flex items-center gap-1">
                            <x-form-modal :id="'edit-defect-'.$defect->id" title="Edit Defect Master Row">
                                <button class="px-2.5 py-1.5 text-xs rounded-md bg-yellow-50 text-yellow-700 hover:bg-yellow-100">Edit</button>
                                <x-slot:content>
                                    <form method="POST" action="{{ route('admin.sewing.settings.defects.update', $defect) }}">@csrf @method('PUT')
                                        @include('admin.sewing.settings._defect_fields', ['defect' => $defect])
                                        <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                                            <button type="button" @click="open=false" class="px-4 py-2 text-sm rounded-md border border-gray-300">Cancel</button>
                                            <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white">Save</button>
                                        </div>
                                    </form>
                                </x-slot:content>
                            </x-form-modal>
                            @if($defect->is_active)
                            <form method="POST" action="{{ route('admin.sewing.settings.defects.destroy', $defect) }}" onsubmit="return confirm('Retire this defect master row? Past log entries are unchanged.')">@csrf @method('DELETE')
                                <button class="px-2.5 py-1.5 text-xs rounded-md bg-red-50 text-red-700 hover:bg-red-100">Retire</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400">No defect master rows.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $defects->links() }}</div>
</div>
@endsection
