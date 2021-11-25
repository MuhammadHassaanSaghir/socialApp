<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use App\Jobs\VerificationEmail;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\token;
use App\Services\tokenService;
use Illuminate\Support\Facades\Hash;

use Throwable;

class UserController extends Controller
{

    public function register(RegisterRequest $request)
    {
        try {
            $request->validated();
            $emailToken = (new tokenService)->emailToken(time());
            $url = url('api/EmailConfirmation/' . $request->email . '/' . $emailToken);
            // Mail::to($request->email)->send(new VerifyEmail($url, 'feroli3485@epeva.com', $request->name));
            VerificationEmail::dispatch($request->email, $url, $request->name)->delay(now()->addSeconds(10));

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
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function verify($email, $hash)
    {
        try {
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
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $request->validated();
            $user = User::where('email', $request->email)->first();

            if (($request->email != $user->email) or (!Hash::check($request->password, $user->password))) {
                return response([
                    'message' => 'Incorrect Credentials',
                    'status' => '401',
                ]);
            } elseif ($user->email_verified_at == null) {
                return response([
                    'message' => 'Please Confirm Your Email',
                ]);
            } else {
            }

            // $token = $user->createToken($user->name)->plainTextToken;
            $token = (new tokenService)->createToken($user->id);

            $already_exist = token::where('user_id', $user->id)->first();
            if ($already_exist) {
                $already_exist->delete();
                token::create([
                    'user_id' => $user->id,
                    'token' => $token,
                ]);
                return response([
                    'User' => new UserResource($user),
                    'token' => $token,
                ]);
            } else {
                token::create([
                    'user_id' => $user->id,
                    'token' => $token,
                ]);

                return response([
                    'user' => new UserResource($user),
                    'token' => $token,
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }


    public function update(Request $request)
    {
        try {
            $request->validate([
                'name' => 'string|min:3',
            ]);
            $user = User::where('id', '=', $request->user_id)->first();
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
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function update_password(UpdatePasswordRequest $request)
    {
        try {
            $request->validated();
            $user = User::find($request->user_id);
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
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        try {
            // auth()->user()->tokens()->delete();
            $token_exist = token::where('user_id', $request->user_id)->first();
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
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
