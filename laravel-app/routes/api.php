<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::post('/signup', [AuthController::class, 'signup'])->name('signup');
Route::post('/signin', [AuthController::class, 'signin'])->name('signin');

// route group for auth:sanctum middleware
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/signout', [AuthController::class, 'signout'])->name('signout');
    Route::get('/verify', [AuthController::class, 'verify'])->name('verify');
});