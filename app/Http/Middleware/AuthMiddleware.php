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
        $decode = JWT::decode($curr_token, new Key('socialApp_key', 'HS256'));
        // dd($decode);
        $token_exist = token::where('user_id', $decode->data)->first();
        // dd($token_exist);

        if (!isset($token_exist)) {
            return response([
                'message' => 'Unauthenticated',
            ]);
        }else{
            return $next($request);
        }
    }
}
