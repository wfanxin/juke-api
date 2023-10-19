<?php

namespace App\Http\Middleware;

use Closure;

class CrossHttp
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
        $response = $next($request);
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Device-Id, X-Token, M-Token');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS, DELETE');
        $response->header('Allow', 'GET, POST, PATCH, PUT, OPTIONS, DELETE');
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('X-Frame-Options', 'SAMEORIGIN');
        return $response;
    }
}
