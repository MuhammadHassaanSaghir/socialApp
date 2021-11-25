<?php

namespace App\Http\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\token;
use App\Services\tokenService;
use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
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
        $curr_token = $request->bearerToken();
        if (empty($curr_token)) {
            return response([
                'message' => 'Please Enter Token',
            ]);
        } else {
            $decoded = (new tokenService)->getToken($request);
            $request = $request->merge(array('user_id' => $decoded));
            $user_exist = token::where('user_id', '=', $request->user_id)->first();
            if (!isset($user_exist)) {
                return response([
                    'message' => 'Unauthenticated',
                ]);
            } else {
                return $next($request);
            }
        }
    }
}
