<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        if ($user) {
            $user->forceFill(['last_login_at' => now()])->save();
            if (! $user->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Contact the administrator.']);
            }

            // Don't send the user back to a page their role cannot access.
            $intended = $request->session()->get('url.intended');
            if ($intended && ! $this->canAccess($user, $intended)) {
                $request->session()->forget('url.intended');
            }

            return redirect()->intended(route($user->homeRoute(), absolute: false));
        }

        return redirect()->route('dashboard');
    }

    /**
     * Determine whether the given URL is reachable for the user's role.
     */
    protected function canAccess(User $user, string $url): bool
    {
        $path = '/'.ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/');

        if (str_starts_with($path, '/admin/cutting')) {
            return $user->hasAnyRole(['admin', 'manager', 'cutting_qc', 'cutting_incharge', 'qam', 'management', 'merchandiser']);
        }

        if (str_starts_with($path, '/admin')) {
            return $user->hasAnyRole(['admin', 'manager']);
        }

        return true;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
