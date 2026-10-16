@extends('layouts.app')

@section('title', 'Cutting Checklists')
@section('header', 'Checklist Masters')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <h3 class="font-semibold text-gray-800 text-sm mb-3">Audit Numbering</h3>
    <form method="POST" action="{{ route('admin.cutting.numbering.update') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">@csrf @method('PUT')
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Prefix</label>
            <input type="text" name="prefix" value="{{ $sequence?->prefix ?? 'CQA-' }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Padding</label>
            <input type="number" min="1" max="10" name="padding" value="{{ $sequence?->padding ?? 4 }}" class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div><button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save numbering</button></div>
    </form>
    @if($sequence)<p class="text-xs text-gray-500 mt-2">Next number: <strong>{{ $sequence->prefix }}{{ str_pad($sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT) }}</strong></p>@endif
</div>

@foreach($templates as $stage => $versions)
    @php $active = $versions->firstWhere('is_active', true) ?? $versions->first(); @endphp
    @if($active)
    <div class="bg-white rounded-lg shadow-sm p-4 mb-4" x-data="{ showAdd: false }">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
            <div>
                <h3 class="font-semibold text-gray-800 text-sm">{{ \App\Models\CuttingAudit::STAGE_LABELS[$stage] }}</h3>
                <p class="text-xs text-gray-500">Active version {{ $active->version }} · {{ $active->checkpoints->where('is_active', true)->count() }} active checkpoints</p>
            </div>
            <form method="POST" action="{{ route('admin.cutting.checklists.new-version', $active) }}" onsubmit="return confirm('Create a new checklist version? Historical audits keep their original version.')">@csrf
                <button class="px-3 py-1.5 text-xs rounded-md border border-gray-300 hover:bg-gray-50">New version</button>
            </form>
        </div>

        <form method="POST" action="{{ route('admin.cutting.checklists.update', $active) }}" class="mb-3">@csrf @method('PUT')
            <label class="block text-xs font-medium text-gray-600 mb-1">Sign-off roles (in order)</label>
            <div class="flex flex-wrap gap-3">
                @foreach($roles as $role)
                    <label class="inline-flex items-center gap-1 text-xs">
                        <input type="checkbox" name="signoff_roles[]" value="{{ $role }}" @checked(in_array($role, $active->signoff_roles ?? []))>
                        {{ ucwords(str_replace('_',' ', $role)) }}
                    </label>
                @endforeach
            </div>
            <button class="mt-2 px-3 py-1.5 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800">Save sign-off roles</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]">
                    <tr><th class="px-3 py-2 text-left">#</th><th class="px-3 py-2 text-left">Checkpoint</th><th class="px-3 py-2 text-left">Rules</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($active->checkpoints as $cp)
                    <tr>
                        <td class="px-3 py-2 text-gray-400">{{ $cp->sort_order }}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-800">{{ $cp->name }}</div>
                            @if($cp->description)<div class="text-[11px] text-gray-500">{{ $cp->description }}</div>@endif
                        </td>
                        <td class="px-3 py-2 text-[10px]">
                            @if($cp->mandatory_remarks_on_not_ok)<span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 mr-1">Remarks on Not OK</span>@endif
                            @if($cp->blocks_approval)<span class="px-1.5 py-0.5 rounded bg-red-100 text-red-700">Blocks approval</span>@endif
                        </td>
                        <td class="px-3 py-2"><x-status-badge :status="$cp->is_active ? 'approved' : 'pending'" :label="$cp->is_active ? 'Active' : 'Retired'" /></td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <form method="POST" action="{{ route('admin.cutting.checkpoints.move', $cp) }}">@csrf<input type="hidden" name="direction" value="up"><button class="px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50">↑</button></form>
                                <form method="POST" action="{{ route('admin.cutting.checkpoints.move', $cp) }}">@csrf<input type="hidden" name="direction" value="down"><button class="px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50">↓</button></form>
                                <x-form-modal :id="'edit-cp-'.$cp->id" title="Edit Checkpoint">
                                    <button class="px-2.5 py-1.5 text-xs rounded-md bg-yellow-50 text-yellow-700 hover:bg-yellow-100">Edit</button>
                                    <x-slot:content>
                                        <form method="POST" action="{{ route('admin.cutting.checkpoints.update', $cp) }}">@csrf @method('PUT')
                                            <div class="space-y-3">
                                                <div><label class="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" name="name" value="{{ $cp->name }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                                <div><label class="block text-xs font-medium text-gray-600 mb-1">Description</label><textarea name="description" rows="2" class="w-full rounded-md border-gray-300 text-sm">{{ $cp->description }}</textarea></div>
                                                <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="mandatory_remarks_on_not_ok" value="1" @checked($cp->mandatory_remarks_on_not_ok)> Mandatory remarks on Not OK</label>
                                                <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="blocks_approval" value="1" @checked($cp->blocks_approval)> Blocks approval</label>
                                                <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($cp->is_active)> Active</label>
                                            </div>
                                            <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                                                <button type="button" @click="open=false" class="px-4 py-2 text-sm rounded-md border border-gray-300">Cancel</button>
                                                <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white">Save</button>
                                            </div>
                                        </form>
                                    </x-slot:content>
                                </x-form-modal>
                                @if($cp->is_active)
                                <form method="POST" action="{{ route('admin.cutting.checkpoints.destroy', $cp) }}" onsubmit="return confirm('Retire this checkpoint? Historical audits keep their data.')">@csrf @method('DELETE')
                                    <button class="px-2.5 py-1.5 text-xs rounded-md bg-red-50 text-red-700 hover:bg-red-100">Retire</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <button @click="showAdd = !showAdd" class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Add checkpoint</button>
            <div x-show="showAdd" x-cloak class="mt-3 border border-gray-200 rounded-md p-3">
                <form method="POST" action="{{ route('admin.cutting.checkpoints.store', $active) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">@csrf
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" name="name" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Description</label><input type="text" name="description" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="mandatory_remarks_on_not_ok" value="1"> Mandatory remarks on Not OK</label>
                    <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="blocks_approval" value="1"> Blocks approval</label>
                    <div class="sm:col-span-2"><button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white">Add</button></div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endsection
