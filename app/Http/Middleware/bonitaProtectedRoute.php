<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class bonitaProtectedRoute
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');
        
        if (!$jsessionid || !$xBonitaAPIToken)
            return response()->json("No cookies set", 400);

        return $next($request);
    }
}
