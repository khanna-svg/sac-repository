<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSacAdmin
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $userRole = (string) $request->session()->get('sac_user_role');

        // If no specific roles specified, allow any authorized staff role
        if (empty($roles)) {
            $roles = ['admin', 'librarian', 'coordinator'];
        }

        // 'admin' has universal access to all admin routes
        if ($userRole === 'admin' || in_array($userRole, $roles, true)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('backend/*')) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized. Required role: ' . implode(' or ', $roles),
            ], 403);
        }

        return redirect()->route('documents')->with('error', 'Unauthorized access.');
    }
}