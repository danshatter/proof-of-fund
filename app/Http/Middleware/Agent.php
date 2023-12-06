<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;
use App\Exceptions\ForbiddenException;

class Agent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->user()->role_id, [
            Role::INDIVIDUAL_AGENT,
            Role::AGENCY
        ])) {
            throw new ForbiddenException;
        }

        return $next($request);
    }
}
