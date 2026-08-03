<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoAgentMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->agent_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Agents cannot access this area.'], 403);
            }
            return redirect()->route('chat.index')
                ->with('warning', __('Agents do not have access to that area.'));
        }

        return $next($request);
    }
}
