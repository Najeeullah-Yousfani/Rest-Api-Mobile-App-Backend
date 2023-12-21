<?php

namespace App\Http\Middleware;

use Closure;

class CheckAdmin
{

    const USER_ROLE_ADMIN       = 1;
    const USER_ROLE_USER        = 2;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->user() && $request->user()->role_id != 'admin')
        {
            return response()->json(['status'  => '403','error'=> array('message' => 'Access denied.')], 403);
        }
        return $next($request);
    }
}
