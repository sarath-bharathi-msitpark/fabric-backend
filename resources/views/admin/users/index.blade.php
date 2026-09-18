@extends('layouts.app')

@section('title', 'User Management')
@section('header', 'User Management')

@section('actions')
    <x-form-modal id="add-user" title="Add User">
        <button class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">+ Add User</button>
        <x-slot:content>
            <form method="POST" action="{{ route('admin.users.store') }}">@csrf
                <div class="space-y-3">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" name="name" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Email *</label><input type="email" name="email" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Password *</label><input type="password" name="password" required minlength="8" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Confirm Password *</label><input type="password" name="password_confirmation" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Role *</label>
                        <select name="role" required class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="Select role...">
                            <option value="">Select role...</option>
                            <option value="admin">Admin</option><option value="manager">Manager</option><option value="viewer">Viewer</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                    <button type="button" @click="open = false" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white text-center hover:bg-blue-700">Create User</button>
                </div>
            </form>
        </x-slot:content>
    </x-form-modal>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm p-4 mb-4 print:hidden">
    <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <div class="sm:col-span-2 md:col-span-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name / email..." class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div>
            <select name="role" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="All Roles">
                <option value="">All Roles</option>
                @foreach(['admin','manager','viewer'] as $r)<option value="{{ $r }}" @selected(request('role')==$r)>{{ ucfirst($r) }}</option>@endforeach
            </select>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Apply</button>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Reset</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">Name</th><th class="px-3 py-2 text-left">Email</th>
                    <th class="px-3 py-2 text-left">Role</th><th class="px-3 py-2 text-left">Active</th>
                    <th class="px-3 py-2 text-left">Last Login</th><th class="px-3 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $user->name }}</td>
                    <td class="px-3 py-2 text-xs">{{ $user->email }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$user->role" /></td>
                    <td class="px-3 py-2">
                        @can('deactivate', $user)
                        <button onclick="toggleUser(this, {{ $user->id }})" class="relative inline-flex h-5 w-10 items-center rounded-full transition {{ $user->is_active ? 'bg-green-500' : 'bg-gray-300' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $user->is_active ? 'translate-x-5' : 'translate-x-1' }}"></span>
                        </button>
                        @else <span class="text-xs">{{ $user->is_active ? 'Yes':'No' }}</span> @endcan
                    </td>
                    <td class="px-3 py-2 text-xs">{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            @can('update', $user)
                            <x-form-modal :id="'edit-'.$user->id" title="Edit User">
                                <button class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-yellow-700 bg-yellow-50 hover:bg-yellow-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.41-9.41a2 2 0 112.83 2.83L11.83 15H9v-2.83l8.59-8.58z"/></svg>
                                    Edit
                                </button>
                            <x-slot:content>
                                <form method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')
                                    <div class="space-y-3">
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" name="name" value="{{ $user->name }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Email *</label><input type="email" name="email" value="{{ $user->email }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Role *</label>
                                            <select name="role" required class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="Select role...">
                                                <option value="">Select role...</option>
                                                @foreach(['admin','manager','viewer'] as $r)<option value="{{ $r }}" @selected($user->role==$r)>{{ ucfirst($r) }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div><label class="flex items-center gap-2 text-xs font-medium text-gray-600"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)> Active</label></div>
                                    </div>
                                    <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-2">
                                        <button type="button" @click="open = false" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Cancel</button>
                                        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white text-center hover:bg-blue-700">Save</button>
                                    </div>
                                </form>
                            </x-slot:content>
                        </x-form-modal>
                        @endcan
                        @can('resetPassword', $user)
                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition" onclick="return confirm('Send password reset link to {{ $user->email }}?')">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Reset
                            </button>
                        </form>
                        @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $users->withQueryString()->links() }}</div>
</div>

@push('scripts')
<script>
async function toggleUser(btn, id) {
    const res = await fetch('{{ route("admin.users.index") }}/' + id + '/deactivate', { method:'PATCH', headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept':'application/json' } });
    const data = await res.json();
    if (data.success) { btn.classList.toggle('bg-green-500', data.is_active); btn.classList.toggle('bg-gray-300', !data.is_active); btn.querySelector('span').classList.toggle('translate-x-5', data.is_active); btn.querySelector('span').classList.toggle('translate-x-1', !data.is_active); }
}
</script>
@endpush
@endsection
