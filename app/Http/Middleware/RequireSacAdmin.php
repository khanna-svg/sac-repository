<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSacAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('sac_user_role') !== 'admin') {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        return $next($request);
    }
}