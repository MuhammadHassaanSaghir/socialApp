<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use App\Models\token;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class EmailVerificationController extends Controller
{

    public function getTokenid(Request $request)
    {
        $curr_token = $request->bearerToken();
        $decode = JWT::decode($curr_token, new Key('socialApp_key', 'HS256'));
        $user_exist = token::where('user_id', $decode->data)->first();
        if ($user_exist) {
            return $user_exist->user_id;
        } else {
            return response([
                'message' => 'Unauthentication',
            ]);
        }
    }

    public function isverifyEmail()
    {
        // $user_id = $this->getTokenid();

        // $user = User::where('id', $request->email)->first();

    }





    public function sendEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return [
                'message' => 'Already Verified'
            ];
        }

        $request->user()->sendEmailVerificationNotification();

        return ['status' => 'Verification Link has been Sent'];
    }

    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return [
                'message' => 'Email already verified'
            ];
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return [
            'message' => 'Email has been verified'
        ];
    }
}
