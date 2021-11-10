<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use App\Models\token;

// use Illuminate\Auth\Events\Verified;
// use Illuminate\Foundation\Auth\EmailVerificationRequest;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Mail;
// use Illuminate\Bus\Queueable;
// use Illuminate\Mail\Mailable;
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


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
            'image' => 'required',
        ]);

        // return User::create($request->all());
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $request->image,
        ]);



        $url = url('api/emailConfirmation/' . $request->email);

        Mail::to($request->email)->send(new VerifyEmail($url, 'feroli3485@epeva.com'));

        return ['status' => 'Verification Link has been Sent. Check Your Mail'];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
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
                'message' => 'Email Address is Incorrect',
                'status' => '401',
            ]);
        }
        if (!Hash::check($request->password, $user->password)) {
            return response([
                'message' => 'Password is Incorrect',
                'status' => '401',
            ]);
        }

        // $token = $user->createToken($user->name)->plainTextToken;
        $already_exist = token::where('user_id', $user->id)->first();
        if ($already_exist) {
            return response([
                'message' => 'This user is already login with this Token',
                'token' => $already_exist->token,
            ]);
        }

        $token = $this->createToken($user->id);
        token::create([
            'user_id' => $user->id,
            'token' => $token,
        ]);

        return response([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        // auth()->user()->tokens()->delete();
        $curr_token = $request->bearerToken();
        $decode = JWT::decode($curr_token, new Key('socialApp_key', 'HS256'));
        // dd($decode);
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

    public function verify($email)
    {
        $user_exist = User::where('email', $email);
        if (!$user_exist) {
            return response([
                'message' => 'Something went wrong',
            ]);
        } elseif ($user_exist->email_verified_at != null) {
            return response([
                'message' => 'Link has been Expired',
            ]);
        } else {
            $user_exist->email_verfied_at = time();
            $user_exist->save();
            return response([
                'message' => 'Verification Link has been Sent. Check Your Mail',
            ]);
        }
    }
}
