<?php

namespace App\Http\Middleware;

use Closure;

class RenewZoomTokenMiddleware
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
        renewZoomAccessToken();

        $response = $next($request);
        

        return $response;
    }
}
