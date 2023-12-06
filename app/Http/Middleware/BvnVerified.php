<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\BvnUnverifiedException;

class BvnVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Load the camouflage relationship
        $request->user()->load(['camouflage']);

        if (!isset($request->user()->camouflage?->verified_at) || !isset($request->user()->camouflage?->image_verified_at)) {
            throw new BvnUnverifiedException;
        }

        return $next($request);
    }
}
