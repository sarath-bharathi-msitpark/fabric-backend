@extends('layouts.app')

@section('title', 'Sewing Operators')
@section('header', 'Sewing Operators Master')

@section('actions')
<x-form-modal id="add-operator" title="Add Operator">
    <button class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">+ Add Operator</button>
    <x-slot:content>
        <form method="POST" action="{{ route('admin.sewing.settings.operators.store') }}">@csrf
            <div class="space-y-3">
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Employee Code *</label><input type="text" name="employee_code" required class="w-full rounded-md border-gray-300 text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" name="name" required class="w-full rounded-md border-gray-300 text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Line</label>
                    <select name="sewing_line_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">—</option>
                        @foreach($lines as $line)<option value="{{ $line->id }}">{{ $line->line_no }}{{ $line->name ? ' — '.$line->name : '' }}</option>@endforeach
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
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs"><tr><th class="px-3 py-2 text-left">Code</th><th class="px-3 py-2 text-left">Name</th><th class="px-3 py-2 text-left">Line</th><th class="px-3 py-2 text-left">Active</th><th class="px-3 py-2 text-left">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($operators as $operator)
                <tr>
                    <td class="px-3 py-2 font-medium">{{ $operator->employee_code }}</td>
                    <td class="px-3 py-2">{{ $operator->name }}</td>
                    <td class="px-3 py-2">{{ $operator->line->line_no ?? '—' }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$operator->is_active ? 'approved' : 'pending'" :label="$operator->is_active ? 'Active' : 'Inactive'" /></td>
                    <td class="px-3 py-2">
                        <div class="inline-flex items-center gap-1">
                            <x-form-modal :id="'edit-operator-'.$operator->id" title="Edit Operator">
                                <button class="px-2.5 py-1.5 text-xs rounded-md bg-yellow-50 text-yellow-700 hover:bg-yellow-100">Edit</button>
                                <x-slot:content>
                                    <form method="POST" action="{{ route('admin.sewing.settings.operators.update', $operator) }}">@csrf @method('PUT')
                                        <div class="space-y-3">
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Employee Code *</label><input type="text" name="employee_code" value="{{ $operator->employee_code }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" name="name" value="{{ $operator->name }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Line</label>
                                                <select name="sewing_line_id" class="w-full rounded-md border-gray-300 text-sm">
                                                    <option value="">—</option>
                                                    @foreach($lines as $line)<option value="{{ $line->id }}" @selected($operator->sewing_line_id === $line->id)>{{ $line->line_no }}{{ $line->name ? ' — '.$line->name : '' }}</option>@endforeach
                                                </select>
                                            </div>
                                            <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($operator->is_active)> Active</label>
                                        </div>
                                        <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                                            <button type="button" @click="open=false" class="px-4 py-2 text-sm rounded-md border border-gray-300">Cancel</button>
                                            <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white">Save</button>
                                        </div>
                                    </form>
                                </x-slot:content>
                            </x-form-modal>
                            @if($operator->is_active)
                            <form method="POST" action="{{ route('admin.sewing.settings.operators.destroy', $operator) }}" onsubmit="return confirm('Deactivate this operator?')">@csrf @method('DELETE')
                                <button class="px-2.5 py-1.5 text-xs rounded-md bg-red-50 text-red-700 hover:bg-red-100">Deactivate</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-3 py-8 text-center text-gray-400">No operators.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $operators->links() }}</div>
</div>
@endsection
