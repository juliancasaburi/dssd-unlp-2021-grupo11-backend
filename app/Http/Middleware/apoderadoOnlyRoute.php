<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class apoderadoOnlyRoute
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
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user->hasRole('apoderado'))
            return response()->json("No tienes los permisos necesarios", 403);
        
            return $next($request);
    }
}
