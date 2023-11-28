<?php

namespace App\Http\Middleware;

use Closure;

class SignupAllowedHostMiddleware
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
        $allowedHosts = explode(',', env('SIGNUP_ALLOWED_DOMAINS'));
        $requestHost = parse_url($request->headers->get('origin'),  PHP_URL_HOST);

        if(!in_array($requestHost, $allowedHosts, false)) {
            return response()->json([
                "error" => true,
                "message" => "request blocked from {$requestHost}"
            ]);
        }

        $response = $next($request);

        return $response;

    }
}
