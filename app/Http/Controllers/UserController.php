<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Models\User;
use App\Models\token;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;

class UserController extends Controller
{

    // Generate Token
    function createToken($user_id)
    {
        date_default_timezone_set('Asia/Karachi');
        $issued_At = time() + 3600;
        $key = "socialApp_key";
        $payload = array(
            "iss" => "http://127.0.0.1:8000",
            "aud" => "http://127.0.0.1:8000",
            "iat" => time(),
            "exp" => $issued_At,
            "data" => $user_id,
        );
        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    function emailToken($data)
    {
        date_default_timezone_set('Asia/Karachi');
        $issued_At = time() + 3600;
        $key = "socialApp_key";
        $payload = array(
            "iss" => "http://127.0.0.1:8000",
            "aud" => "http://127.0.0.1:8000",
            "iat" => time(),
            "exp" => $issued_At,
            "data" => $data,
        );
        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|confirmed',
            'image' => 'required',
        ]);

        $emailToken = $this->emailToken(time());
        $url = url('api/EmailConfirmation/' . $request->email . '/' . $emailToken);
        Mail::to($request->email)->send(new VerifyEmail($url, 'feroli3485@epeva.com', $request->name));
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $request->file('image')->store('user_images'),
            'remember_token' => $emailToken,
        ]);
        if ($user) {
            return response([
                'message' => 'Verification Link has been Sent. Check Your Mail',
            ]);
        } else {
            return response([
                'message' => 'Something Went Wrong While Sending Email',
            ]);
        }
    }

    public function verify($email, $hash)
    {
        $user_exist = User::where('email', $email)->first();
        if (!$user_exist) {
            return response([
                'message' => 'Something went wrong',
            ]);
        } elseif ($user_exist->email_verified_at != null) {
            return response([
                'message' => 'Link has been Expired',
            ]);
        } elseif ($user_exist->remember_token != $hash) {
            return response([
                'message' => 'Unauthenticated',
            ]);
        } else {
            $user_exist->email_verified_at = time();
            $user_exist->save();
            return response([
                'message' => 'Now your SocialApp Account has been Verified',
            ]);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response([
                'message' => 'Please Register First!',
                'status' => '401',
            ]);
        } elseif ($request->email != $user->email) {
            return response([
                'message' => 'Email Address is Incorrect',
                'status' => '401',
            ]);
        } elseif (!Hash::check($request->password, $user->password)) {
            return response([
                'message' => 'Password is Incorrect',
                'status' => '401',
            ]);
        } elseif ($user->email_verified_at == null) {
            return response([
                'message' => 'Please Confirm Your Email',
            ]);
        } else {
        }

        // $token = $user->createToken($user->name)->plainTextToken;
        $token = $this->createToken($user->id);
        $already_exist = token::where('user_id', $user->id)->first();
        if ($already_exist) {
            $already_exist->delete();
            token::create([
                'user_id' => $user->id,
                'token' => $token,
            ]);
            return response([
                'user' => $user,
                'token' => $token,
            ]);
        } else {
            token::create([
                'user_id' => $user->id,
                'token' => $token,
            ]);

            return response([
                'user' => $user,
                'token' => $token,
            ]);
        }
    }


    public function update(Request $request)
    {
        $currToken = $request->bearerToken();
        $decode = JWT::decode($currToken, new Key('socialApp_key', 'HS256'));

        $request->validate([
            'name' => 'string|min:3',
        ]);
        $user = User::where('user_id', '=', $decode->data)->first();
        if (isset($user)) {
            if (isset($request->name)) {
                $user->name = $request->name;
                $user->save();
            }
            if (isset($request->image)) {
                unlink(storage_path('app/' . $user->image));
                $user->image = $request->file('image')->store('user_images');
                $user->save();
            }
            return response([
                'message' => 'Profile Updated',
            ]);
        } else {
            return response([
                'message' => 'No User Found',
            ]);
        }
    }

    public function update_password(Request $request)
    {
        $currToken = $request->bearerToken();
        $decode = JWT::decode($currToken, new Key('socialApp_key', 'HS256'));

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);
        $user = User::find($decode->data);
        $check_pass = Hash::check($request->current_password, $user->password);
        if (($user and $check_pass) == true) {
            $password_update = $user->update(['password' => Hash::make($request->new_password)]);
            if (isset($password_update)) {
                return response([
                    'message' => 'Password Updated Successfully',
                ]);
            } else {
                return response([
                    'message' => 'Something Went Wrong',
                ]);
            }
        } else {
            return response([
                'message' => 'Your Current Password is Wrong',
            ]);
        }
    }

    public function logout(Request $request)
    {
        // auth()->user()->tokens()->delete();
        $currToken = $request->bearerToken();
        $decode = JWT::decode($currToken, new Key('socialApp_key', 'HS256'));
        $token_exist = token::where('user_id', $decode->data)->first();
        if ($token_exist) {
            $token_exist->delete();
            return response([
                'message' => 'Logout Successfully',
            ]);
        } else {
            return response([
                'message' => 'Already Logout',
            ]);
        }
    }
}
