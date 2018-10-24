<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use ApiResponse;

class SiteRequired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Load the site from $_GET
        if ($request->get('site_id'))
        {
            $user = Auth::guard('api')->user();
            if ($user)
            {
                if ($user->sites()->find($request->get('site_id')))
                {
                    return $next($request);
                }

                return ApiResponse::notFound();
            }

            return ApiResponse::unauthorized();
        }

        return ApiResponse::badRequest('The the site_id parameter is required.');
    }
}
