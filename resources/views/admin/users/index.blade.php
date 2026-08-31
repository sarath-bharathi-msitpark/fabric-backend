@extends('layouts.app')

@section('title', 'User Management')
@section('header', 'User Management')

@section('actions')
    <x-form-modal id="add-user" title="Add User">
        <button class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Add User</button>
        <x-slot:content>
            <form method="POST" action="{{ route('admin.users.store') }}">@csrf
                <div class="space-y-3">
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" name="name" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Email *</label><input type="email" name="email" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Password *</label><input type="password" name="password" required minlength="8" class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Confirm Password *</label><input type="password" name="password_confirmation" required class="w-full rounded-md border-gray-300 text-sm"></div>
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">Role *</label>
                        <select name="role" required class="w-full rounded-md border-gray-300 text-sm">
                            <option value="admin">Admin</option><option value="manager">Manager</option><option value="viewer">Viewer</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="open = false" class="px-3 py-1.5 text-sm border rounded-md">Cancel</button>
                    <button type="submit" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md">Create User</button>
                </div>
            </form>
        </x-slot:content>
    </x-form-modal>
@endsection

@section('content')
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
                        @can('update', $user)
                        <x-form-modal :id="'edit-'.$user->id" title="Edit User">
                            <button class="text-yellow-600 hover:underline text-xs">Edit</button>
                            <x-slot:content>
                                <form method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')
                                    <div class="space-y-3">
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" name="name" value="{{ $user->name }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Email *</label><input type="email" name="email" value="{{ $user->email }}" required class="w-full rounded-md border-gray-300 text-sm"></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">Role *</label>
                                            <select name="role" required class="w-full rounded-md border-gray-300 text-sm">
                                                @foreach(['admin','manager','viewer'] as $r)<option value="{{ $r }}" @selected($user->role==$r)>{{ ucfirst($r) }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div><label class="flex items-center gap-2 text-xs font-medium text-gray-600"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)> Active</label></div>
                                    </div>
                                    <div class="mt-4 flex justify-end gap-2">
                                        <button type="button" @click="open = false" class="px-3 py-1.5 text-sm border rounded-md">Cancel</button>
                                        <button type="submit" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md">Save</button>
                                    </div>
                                </form>
                            </x-slot:content>
                        </x-form-modal>
                        @endcan
                        @can('resetPassword', $user)
                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-blue-600 hover:underline text-xs ml-2" onclick="return confirm('Send password reset link to {{ $user->email }}?')">Reset Password</button>
                        </form>
                        @endcan
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
