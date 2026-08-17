<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Validate user existence, password, and admin role
        if (
            $user &&
            Hash::check($request->password, $user->password) &&
            $user->role === 'admin'
        ) {
            $request->session()->regenerate();

            $request->session()->put([
                'sac_user_email' => strtolower($user->email),
                'sac_user_role' => 'admin',
            ]);

            return redirect()->route('admin.upload');
        }

        return back()->withErrors([
            'email' => 'Invalid admin credentials or account is not an admin.',
        ]);
    }
}