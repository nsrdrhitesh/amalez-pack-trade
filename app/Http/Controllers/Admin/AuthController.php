<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller {
    public function loginForm() { return view('admin.auth.form', ['register' => false]); }
    public function registerForm() { return view('admin.auth.form', ['register' => true]); }
    public function register(Request $request) {
        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','confirmed',Password::min(12)->mixedCase()->numbers()],
        ]);
        $user = new User($data);
        $user->is_admin = true;
        $user->save();
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('admin.dashboard')->with('status', 'Your local administrator account is ready.');
    }
    public function login(Request $request) {
        $data = $request->validate(['email' => ['required','email'], 'password' => ['required','string'], 'remember' => ['sometimes','boolean']]);
        $key = 'admin-login:'.Str::transliterate(Str::lower($data['email'])).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'is_admin' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'These credentials do not match an administrator account.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        return redirect()->intended(route('admin.dashboard'));
    }
    public function logout(Request $request) {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
