<?php

namespace App\Http\Middleware;

use Closure;

class WXLogin
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
        $token = $request->token;
        if (!getUserToken($token)) {
            return response()->json([
                'msg'=>'请先登录！'
            ],400);
        }
        return $next($request);
    }
}
