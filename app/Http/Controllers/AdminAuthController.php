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

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (
            $user &&
            Hash::check($request->password, $user->password) &&
            $user->role === 'admin'
        ) {

            $request->session()->regenerate();

            $request->session()->put(
                'sac_user_role',
                'admin'
            );

            $request->session()->put(
                'sac_user_email',
                strtolower($user->email)
            );

            return redirect()->route('admin.analytics');
        }


        return back()->withErrors([
            'email' => 'Invalid admin credentials or account is not an admin.',
        ]);
    }
}