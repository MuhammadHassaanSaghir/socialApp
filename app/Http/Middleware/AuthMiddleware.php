<?php

namespace App\Http\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\token;
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
            $decode = JWT::decode($curr_token, new Key(config('JwtConstant.secret_key'), 'HS256'));
            $token_exist = token::where('user_id', $decode->data)->first();

            if (!isset($token_exist)) {
                return response([
                    'message' => 'Unauthenticated',
                ]);
            } else {
                return $next($request);
            }
        }
    }
}
