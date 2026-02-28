<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [ChatController::class, 'index'])->name('chat.index');
Route::get('/c/{conversation}', [ChatController::class, 'index'])->name('chat.show');
Route::post('/chat', [ChatController::class, 'send'])->name('chat.send')->middleware('throttle:60,1');
Route::post('/conversations', [ChatController::class, 'create'])->name('conversations.create');
Route::delete('/conversations', [ChatController::class, 'destroyAll'])->name('conversations.destroyAll');
Route::delete('/conversations/{conversation}', [ChatController::class, 'destroy'])->name('conversations.destroy');

// Auth Routes
Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::post('/auth/check-email', [AuthController::class, 'checkEmail']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/verify', [AuthController::class, 'verify']);

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('auth.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('auth.callback');

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->name('logout');
