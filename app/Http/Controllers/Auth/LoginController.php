<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        // E-posta ya da kullanıcı adı ile giriş
        $login = trim($input['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [$field => mb_strtolower($login), 'password' => $input['password']];

        $key = 'login:'.mb_strtolower($login).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Çok fazla deneme yaptınız. Lütfen '.RateLimiter::availableIn($key).' saniye sonra tekrar deneyin.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            LoginLog::record($request, User::where($field, mb_strtolower($login))->first(), $login, false);

            throw ValidationException::withMessages([
                'login' => 'Kullanıcı adı / e-posta veya şifre hatalı.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        LoginLog::record($request, $request->user(), $login, true);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
