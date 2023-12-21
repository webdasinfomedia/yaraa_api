<?php

namespace App\Http\Middleware;

use Closure;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        // Pre-Middleware Action
        $roles = array_filter(explode('|', $roles));

        if (!$request->user()->role()->exists()) {
            return response()->json([
                "error" => true,
                'message' => 'User does not have any role',
                'data' => null
            ], 401);
        }

        if (!in_array($request->user()->role->slug, $roles)) {
            return response()->json([
                "error" => true,
                'message' => 'You are not authorized for this action',
                'data' => null
            ], 401);
        }

        $response = $next($request);

        // Post-Middleware Action

        return $response;
    }
}
