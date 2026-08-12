<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('login', [
            'pendingEmail' => $request->session()->get('pending_email'),
        ]);
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($request->email));

        if (!preg_match('/^[^@\s]+@sac\.edu\.ph$/', $email)) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Only @sac.edu.ph email accounts can access this system.',
                ]);
        }

        $baseUrl = rtrim((string) getenv('SUPABASE_URL'), '/');
        $key = (string) getenv('SUPABASE_PUBLISHABLE_KEY');

        if ($baseUrl === '' || $key === '') {
            return back()->withErrors([
                'email' => 'Login service is not configured.',
            ]);
        }

        $response = Http::withHeaders([
            'apikey' => $key,
            'Authorization' => "Bearer {$key}",
        ])->post("{$baseUrl}/auth/v1/otp", [
            'email' => $email,
            'create_user' => true,
        ]);

        if (!$response->successful()) {
            return back()->withErrors([
                'email' => 'Could not send a login code. Please try again.',
            ]);
        }

        $request->session()->put('pending_email', $email);

        return redirect('/login')->with(
            'success',
            'A login code was sent to your SAC email.'
        );
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:8'],
        ]);

        $email = $request->session()->get('pending_email');

        if (!$email) {
            return redirect('/login')->withErrors([
                'email' => 'Enter your SAC email first.',
            ]);
        }

        $baseUrl = rtrim((string) getenv('SUPABASE_URL'), '/');
        $key = (string) getenv('SUPABASE_PUBLISHABLE_KEY');

        $response = Http::withHeaders([
            'apikey' => $key,
            'Authorization' => "Bearer {$key}",
        ])->post("{$baseUrl}/auth/v1/verify", [
            'email' => $email,
            'token' => $request->code,
            'type' => 'email',
        ]);

        if (!$response->successful()) {
            return back()->withErrors([
                'code' => 'Invalid or expired code. Request a new login code.',
            ]);
        }

        $user = $response->json('user');

        if (!$user || !str_ends_with(strtolower($user['email'] ?? ''), '@sac.edu.ph')) {
            return redirect('/login')->withErrors([
                'email' => 'Only @sac.edu.ph accounts can access this system.',
            ]);
        }

        $request->session()->regenerate();

        $request->session()->put('sac_user_email', strtolower($user['email']));
        $request->session()->forget('pending_email');

        return redirect('/documents');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}