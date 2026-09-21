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
            in_array($user->role, ['admin', 'librarian', 'coordinator'], true)
        ) {

            $request->session()->regenerate();

            $request->session()->put(
                'sac_user_role',
                $user->role
            );

            $request->session()->put(
                'sac_user_email',
                strtolower($user->email)
            );

            $request->session()->put(
                'sac_user_name',
                $user->name ?? 'Administrator'
            );

            if ($user->role === 'coordinator') {
                return redirect()->route('admin.analytics');
            } elseif ($user->role === 'librarian') {
                return redirect()->route('admin.submissions');
            }

            return redirect()->route('admin.analytics');
        }


        return back()->withErrors([
            'email' => 'Invalid credentials or account is not authorized.',
        ]);
    }
}