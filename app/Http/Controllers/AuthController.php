<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * Display login view.
     * If user is already authenticated, redirect them directly to their role dashboard.
     */
    public function showLogin(Request $request)
    {
        $email = $request->session()->get('sac_user_email');
        $role = $request->session()->get('sac_user_role');

        if ($email && str_ends_with(strtolower($email), '@sac.edu.ph')) {
            if ($role === 'admin') {
                return redirect()->route('admin.upload');
            }
            return redirect()->route('documents');
        }

        return view('login', [
            'pendingEmail' => $request->session()->get('pending_email'),
        ]);
    }

    /**
     * Send 6-digit OTP code to student's @sac.edu.ph institutional email.
     */
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
                    'email' => 'Only official @sac.edu.ph institutional email accounts can access this repository.',
                ]);
        }

        $baseUrl = rtrim((string) env('SUPABASE_URL'), '/');
        $key = (string) env('SUPABASE_PUBLISHABLE_KEY');

        if ($baseUrl === '' || $key === '') {
            return back()->withErrors([
                'email' => 'Login service is not properly configured.',
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
                'email' => 'Could not send login code. Please verify your email and try again.',
            ]);
        }

        $request->session()->put('pending_email', $email);

        return redirect('/login')->with('success', "A login code has been sent to {$email}. Please check your inbox.");
    }

    /**
     * Verify OTP code sent to the student (supports 6 to 8 digits).
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:8'],
        ]);

        $email = $request->session()->get('pending_email');

        if (!$email) {
            return redirect('/login')->withErrors([
                'email' => 'Please enter your SAC student email first.',
            ]);
        }

        $baseUrl = rtrim((string) env('SUPABASE_URL'), '/');
        $key = (string) env('SUPABASE_PUBLISHABLE_KEY');

        $response = Http::withHeaders([
            'apikey' => $key,
            'Authorization' => "Bearer {$key}",
        ])->post("{$baseUrl}/auth/v1/verify", [
            'email' => $email,
            'token' => trim($request->code),
            'type' => 'email',
        ]);

        if (!$response->successful()) {
            return back()->withErrors([
                'code' => 'Invalid or expired login code. Please request a new one.',
            ]);
        }

        $user = $response->json('user');

        if (!$user || !str_ends_with(strtolower($user['email'] ?? ''), '@sac.edu.ph')) {
            return redirect('/login')->withErrors([
                'email' => 'Only official @sac.edu.ph institutional accounts can access this repository.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('sac_user_email', strtolower($user['email']));
        $request->session()->put('sac_user_role', 'student');
        $request->session()->forget('pending_email');

        return redirect()->route('documents');
    }

    /**
     * Cancel pending OTP code verification and reset to email input.
     */
    public function resetLogin(Request $request)
    {
        $request->session()->forget('pending_email');
        return redirect('/login');
    }

    /**
     * Terminate user session and redirect to login.
     */
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}