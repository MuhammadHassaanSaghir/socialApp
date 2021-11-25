<?php

namespace App\Services;

use Illuminate\Http\Request;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class tokenService
{
    protected $key;
    protected $payload;

    public function createToken($user_id)
    {
        try {
            date_default_timezone_set('Asia/Karachi');
            $issued_At = time() + 3600;
            $payload = array(
                "iss" => "http://127.0.0.1:8000",
                "aud" => "http://127.0.0.1:8000",
                "iat" => time(),
                "exp" => $issued_At,
                "data" => $user_id,
            );
            $jwt = JWT::encode($payload, config('JwtConstant.secret_key'), 'HS256');
            return $jwt;
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function emailToken($data)
    {
        date_default_timezone_set('Asia/Karachi');
        $issued_At = time() + 3600;
        $payload = array(
            "iss" => "http://127.0.0.1:8000",
            "aud" => "http://127.0.0.1:8000",
            "iat" => time(),
            "exp" => $issued_At,
            "data" => $data,
        );
        $jwt = JWT::encode($payload, config('JwtConstant.secret_key'), 'HS256');
        return $jwt;
    }

    public function getToken(Request $request)
    {
        $currToken = $request->bearerToken();
        $decode = JWT::decode($currToken, new Key(config('JwtConstant.secret_key'), 'HS256'));
        return $decode->data;
    }
}
