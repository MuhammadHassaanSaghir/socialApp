<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;

function createToken($user_id)
{
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
}

function emailToken($data)
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

if (!function_exists('getToken')) {
    function getToken(Request $request)
    {
        $currToken = $request->bearerToken();
        $decode = JWT::decode($currToken, new Key(config('JwtConstant.secret_key'), 'HS256'));
        return $decode->data;
    }
}
