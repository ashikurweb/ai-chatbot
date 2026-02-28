<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function checkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        return response()->json([
            'exists' => (bool) $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], true)) {
            $request->session()->regenerate();
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $otp = rand(100000, 999999);
        
        Cache::put("register_data:{$request->email}", [
            'password' => Hash::make($request->password),
            'otp' => $otp,
        ], now()->addMinutes(10));

        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json(['success' => true]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $data = Cache::get("register_data:{$request->email}");

        if (!$data || (string)$data['otp'] !== (string)$request->otp) {
            return response()->json(['error' => 'Invalid or expired OTP'], 400);
        }

        $user = User::create([
            'email' => $request->email,
            'name' => explode('@', $request->email)[0], // Basic name from email
            'password' => $data['password'],
        ]);

        Auth::login($user, true);
        Cache::forget("register_data:{$request->email}");
        $request->session()->regenerate();

        return response()->json(['success' => true]);
    }
}
