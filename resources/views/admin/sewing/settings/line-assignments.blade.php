@extends('layouts.app')

@section('title', 'Line Assignments')
@section('header', 'Sewing Line Assignments')

@section('content')
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800 text-sm">
        Assign sewing lines to QC, supervisors and production managers
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">User</th>
                    <th class="px-3 py-2 text-left">Role</th>
                    <th class="px-3 py-2 text-left">Lines</th>
                    <th class="px-3 py-2 text-left">Save</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                @php $userLineIds = $assigned[$user->id] ?? []; @endphp
                <tr>
                    <td class="px-3 py-2">
                        <div class="font-medium text-gray-800">{{ $user->name }}</div>
                        <div class="text-xs text-gray-400">{{ $user->email }}</div>
                    </td>
                    <td class="px-3 py-2 text-gray-600">{{ $user->roles->pluck('name')->implode(', ') }}</td>
                    <td class="px-3 py-2">
                        <form method="POST" action="{{ route('admin.sewing.settings.line-assignments.store') }}" id="assign-{{ $user->id }}" class="flex flex-wrap gap-x-4 gap-y-1">@csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            @foreach($lines as $line)
                                <label class="inline-flex items-center gap-1 text-xs">
                                    <input type="checkbox" name="sewing_line_ids[]" value="{{ $line->id }}" @checked(in_array($line->id, $userLineIds))>
                                    {{ $line->line_no }}
                                </label>
                            @endforeach
                            @if($lines->isEmpty())<span class="text-xs text-gray-400">No active lines.</span>@endif
                        </form>
                    </td>
                    <td class="px-3 py-2">
                        <button type="submit" form="assign-{{ $user->id }}" class="px-3 py-1 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800">Save</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-3 py-8 text-center text-gray-400">No assignable users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="mt-2 text-xs text-gray-400">Assignments scope each user to their lines for the live board and dashboard.</p>
@endsection
