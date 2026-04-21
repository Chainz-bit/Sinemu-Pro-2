<?php

namespace App\Http\Controllers\Super\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if ($this->isDatabaseResponsive() && Auth::guard('super_admin')->check()) {
            return redirect()->route('super.dashboard');
        }

        return view('super.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'login' => $request->input('login') !== null ? (string) $request->input('login') : null,
            'password' => $request->input('password') !== null ? (string) $request->input('password') : null,
        ]);

        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (!$this->isDatabaseResponsive()) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'Layanan autentikasi sedang tidak responsif. Coba lagi beberapa saat.']);
        }

        $loginInput = trim($validated['login']);
        $normalizedLogin = strtolower($loginInput);
        $loginField = filter_var($normalizedLogin, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $loginField => $normalizedLogin,
            'password' => $validated['password'],
        ];

        if (Auth::guard('super_admin')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->route('super.dashboard');
        }

        return back()
            ->withInput($request->only('login'))
            ->withErrors(['login' => 'Kredensial super admin tidak valid.']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('super_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super.login');
    }

    private function isDatabaseResponsive(): bool
    {
        $defaultConnection = (string) config('database.default', 'mysql');
        $connection = (array) config('database.connections.' . $defaultConnection, []);
        $driver = (string) ($connection['driver'] ?? '');

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return true;
        }

        $host = (string) ($connection['host'] ?? '');
        $port = (int) ($connection['port'] ?? 3306);

        if ($host === '' || $port <= 0) {
            return false;
        }

        if (!in_array($host, ['127.0.0.1', 'localhost'], true)) {
            return true;
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, 0.25);
        if (!is_resource($socket)) {
            return false;
        }

        stream_set_timeout($socket, 0, 250000);
        $probe = @fread($socket, 1);
        $meta = stream_get_meta_data($socket);
        fclose($socket);

        return !($probe === false || ($probe === '' && (($meta['timed_out'] ?? false) === true)));
    }
}
