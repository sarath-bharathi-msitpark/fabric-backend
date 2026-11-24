@extends('layouts.app')

@section('title', 'Round Configuration')
@section('header', 'Round Configuration')

@section('content')
@php
    $rows = $rounds->keyBy('round_no');
    $maxRound = max(4, $rounds->max('round_no') ?? 4);
@endphp
<form method="POST" action="{{ route('admin.sewing.settings.rounds.update') }}" class="bg-white rounded-lg shadow-sm p-6 max-w-3xl">@csrf @method('PUT')
    <p class="text-sm text-gray-500 mb-4">Set the inspection hour window for each round. Future rounds stay locked on the grid until their hour.</p>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr><th class="px-3 py-2 text-left">Round</th><th class="px-3 py-2 text-left">Start</th><th class="px-3 py-2 text-left">End</th><th class="px-3 py-2 text-left">Active</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @for($r = 1; $r <= $maxRound; $r++)
                @php($row = $rows->get($r))
                <tr>
                    <td class="px-3 py-2 font-medium">Round {{ $r }}
                        <input type="hidden" name="rounds[{{ $r }}][round_no]" value="{{ $r }}">
                    </td>
                    <td class="px-3 py-2"><input type="time" name="rounds[{{ $r }}][start_time]" value="{{ optional($row)->start_time ? \Illuminate\Support\Str::substr($row->start_time, 0, 5) : '' }}" class="rounded-md border-gray-300 text-sm"></td>
                    <td class="px-3 py-2"><input type="time" name="rounds[{{ $r }}][end_time]" value="{{ optional($row)->end_time ? \Illuminate\Support\Str::substr($row->end_time, 0, 5) : '' }}" class="rounded-md border-gray-300 text-sm"></td>
                    <td class="px-3 py-2"><input type="checkbox" name="rounds[{{ $r }}][is_active]" value="1" @checked($row ? $row->is_active : true)></td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
    <div class="mt-6 flex justify-end">
        <button class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save configuration</button>
    </div>
</form>
@endsection
