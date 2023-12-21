<?php

namespace App\Http\Middleware;

use Closure;

class CheckAdminRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Pre-Middleware Action
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => true, 'message' => 'You dont have access for this.', 'data' => null], 401);
        }

        $response = $next($request);

        // Post-Middleware Action

        return $response;
    }
}
