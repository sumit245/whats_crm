<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class appInstalled
{
    /**
     * Handle an incoming request.
     * App is always considered installed - installer has been removed.
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
?>