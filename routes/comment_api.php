<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommentController;

Route::middleware(['myauths'])->group(function () {

    //COMMENTS ROUTES
    Route::post('/CreateComment', [CommentController::class, 'create'])->middleware('checkfriend');;
    Route::post('/UpdateComment/{id}', [CommentController::class, 'update'])->middleware('checkfriend');;
    Route::delete('/DeleteComment/{id}', [CommentController::class, 'delete']);
});
