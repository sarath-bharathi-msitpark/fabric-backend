@extends('layouts.app')

@section('title', 'Style Operations')
@section('header', 'Style Operation Sequence')

@section('actions')
<x-form-modal id="add-style-op" title="Add Operation to Style">
    <button class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap" @disabled(! $selected)>+ Add Operation</button>
    <x-slot:content>
        @if($selected)
        <form method="POST" action="{{ route('admin.sewing.settings.style-operations.store') }}">@csrf
            <input type="hidden" name="style_id" value="{{ $selected->id }}">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Operation *</label>
                    <select name="sewing_operation_id" required class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select operation</option>
                        @foreach($operations as $op)<option value="{{ $op->id }}">{{ $op->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Default machine</label>
                    <select name="sewing_machine_id" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">—</option>
                        @foreach($machines as $m)<option value="{{ $m->id }}">{{ $m->machine_no }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                <button type="button" @click="open=false" class="px-4 py-2 text-sm rounded-md border border-gray-300">Cancel</button>
                <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white">Add</button>
            </div>
        </form>
        @else
        <p class="text-sm text-gray-500">Create a style first.</p>
        @endif
    </x-slot:content>
</x-form-modal>
@endsection

@section('content')
<form method="GET" action="{{ route('admin.sewing.settings.style-operations.index') }}" class="mb-4 flex items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Style</label>
        <select name="style_id" class="rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
            @foreach($styles as $style)<option value="{{ $style->id }}" @selected($selected && $selected->id === $style->id)>{{ $style->style_number }}</option>@endforeach
        </select>
    </div>
    <noscript><button class="px-4 py-2 text-sm rounded-md bg-slate-700 text-white">Load</button></noscript>
</form>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800 text-sm">
        {{ $selected ? $selected->style_number : 'No style' }} — operation sequence
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">#</th>
                    <th class="px-3 py-2 text-left">Operation</th>
                    <th class="px-3 py-2 text-left">Default machine</th>
                    <th class="px-3 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($styleOps as $styleOp)
                <tr>
                    <td class="px-3 py-2 text-gray-400">{{ $styleOp->sequence_no }}</td>
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $styleOp->operation->name ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $styleOp->machine->machine_no ?? '—' }}</td>
                    <td class="px-3 py-2">
                        <div class="inline-flex items-center gap-1">
                            <form method="POST" action="{{ route('admin.sewing.settings.style-operations.move', $styleOp) }}">@csrf
                                <input type="hidden" name="direction" value="up">
                                <button class="px-2 py-1 text-xs rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100" @disabled($loop->first)>↑</button>
                            </form>
                            <form method="POST" action="{{ route('admin.sewing.settings.style-operations.move', $styleOp) }}">@csrf
                                <input type="hidden" name="direction" value="down">
                                <button class="px-2 py-1 text-xs rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100" @disabled($loop->last)>↓</button>
                            </form>
                            <form method="POST" action="{{ route('admin.sewing.settings.style-operations.destroy', $styleOp) }}" onsubmit="return confirm('Remove this operation from the style?')">@csrf @method('DELETE')
                                <button class="px-2 py-1 text-xs rounded-md bg-red-50 text-red-700 hover:bg-red-100">Remove</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-3 py-8 text-center text-gray-400">No operations mapped to this style yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="mt-2 text-xs text-gray-400">These operations load automatically when an inspection sheet is created for the style. Order here defines the default sequence.</p>
@endsection
