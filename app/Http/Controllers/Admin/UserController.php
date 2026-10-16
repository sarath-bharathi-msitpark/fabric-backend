<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'role']);

        $users = User::with('roles')
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where('name', 'like', "%{$v}%")
                    ->orWhere('email', 'like', "%{$v}%");
            })
            ->when($filters['role'] ?? null, fn ($q, $v) => $q->whereHas('roles', fn ($r) => $r->where('name', $v)))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles', 'filters'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['roles'][0],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->syncRoles($data['roles']);

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} created.");
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email,'.$user->id,
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
            'is_active' => 'nullable|boolean',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['roles'][0],
            'is_active' => $request->boolean('is_active'),
        ]);
        $user->syncRoles($data['roles']);

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorize('resetPassword', $user);
        $status = Password::sendResetLink(['email' => $user->email]);

        return back()->with(
            $status === Password::RESET_LINK_SENT ? 'success' : 'error',
            __($status)
        );
    }

    public function deactivate(Request $request, User $user)
    {
        $this->authorize('deactivate', $user);
        $user->update(['is_active' => ! $user->is_active]);

        return response()->json(['success' => true, 'is_active' => $user->is_active]);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
