<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSacLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $email = (string) $request->session()->get('sac_user_email');

        if (!str_ends_with(strtolower($email), '@sac.edu.ph')) {
            return redirect('/login');
        }

        return $next($request);
    }
}