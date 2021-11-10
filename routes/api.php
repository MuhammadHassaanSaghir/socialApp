<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmailVerificationController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', [UserController::class, 'store']);

Route::post('/login', [UserController::class, 'login']);

// Route::post('/logout', [UserController::class, 'logout']);


Route::middleware(['myauths'])->group(function () {
    // Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendEmail']);
    // Route::get('emailConfirmation/{email}', [UserController::class, 'verify']);
    Route::post('/logout', [UserController::class, 'logout']);
});
Route::get('EmailConfirmation/{email}/{hash}', [UserController::class, 'verify']);

