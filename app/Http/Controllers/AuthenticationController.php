<?php

namespace App\Http\Controllers;

use App\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthenticationController extends Controller
{
    public function authGoogle()
    {
        $validator = \validator(request()->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $client = new \Google_Client(['client_id' => config('services.google.Client_id')]);
        $payload = $client->verifyIdToken(request()->token);
        if ($payload) {
            $userid = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'];

            $user = \App\Models\User::where('social_media_provider', 'google')->where('social_media_id', $userid)->first();
            if (!is_null($user)) {
                $token = $user->createToken(config('app.name'))->plainTextToken;
                return ResponseFormatter::success([
                    'token' => $token,
                ]);
            }

            $user = \App\Models\User::where('email', $email)->first();
            if (!is_null($user)) {
                $user->update([
                    'social_media_provider' => 'google',
                    'social_media_id' => $userid,
                ]);
            } else {
                $user = \App\Models\User::create([
                    'name' => $name,
                    'email' => $email,
                    'social_media_provider' => 'google',
                    'social_media_id' => $userid,
                ]);
            }
            $token = $user->createToken(config('app.name'))->plainTextToken;
            return ResponseFormatter::success([
                'token' => $token,
            ]);

        } else {
            return ResponseFormatter::error(400, null, [
                'Invalid token!',
            ]);
        }
    }
    public function register()
    {
        $validator = \validator(request()->all(), [
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        do {
            $genOTP = rand(100000, 999999);

            $otpCount = User::where('otp_register', $genOTP)->count();
        } while ($otpCount > 0);

        $user = User::create([
            'email' => request()->email,
            'name' => request()->email,
            'otp_register' => $genOTP,
        ]);

        Mail::to($user->email)->send(new \App\Mail\SendRegisterOTP($user));

        return ResponseFormatter::success([
            'is_sent' => true,
        ]);
    }

    public function resendOtp()
    {
        $validator = \validator(request()->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $user = User::where('email', request()->email)->whereNotNull('otp_register')->first();
        if (is_null($user)) {
            return ResponseFormatter::error(400, null, [
                'User tidak ditemukan',
            ]);
        }

        do {
            $genOTP = rand(100000, 999999);

            $otpCount = User::where('otp_register', $genOTP)->count();
        } while ($otpCount > 0);

        $user->update([
            'otp_register' => $genOTP,
        ]);

        Mail::to($user->email)->send(new \App\Mail\SendRegisterOTP($user));

        return ResponseFormatter::success([
            'is_sent' => true,
        ]);
    }

    public function verifyOtp()
    {
        $validator = \validator(request()->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|exists:users,otp_register',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $user = User::where('email', request()->email)->where('otp_register', request()->otp)->count();

        if ($user > 0) {
            return ResponseFormatter::success([
                'is_valid' => true,
            ]);
        }

        return ResponseFormatter::error(400, 'Invalid OTP');
    }

    public function verifyRegister()
    {
        $validator = \validator(request()->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|exists:users,otp_register',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $user = User::where('email', request()->email)->where('otp_register', request()->otp)->first();
        if (!is_null($user)) {
            $user->update([
                'otp_register' => null,
                'email_verified_at' => now(),
                'password' => bcrypt(request()->password),
            ]);

            $token = $user->createToken(config('app.name'))->plainTextToken;

            return ResponseFormatter::success([
                'token' => $token,
            ]);
        }
        return ResponseFormatter::error(400, 'Invalid OTP');
    }

    public function login()
    {
        $validator = \validator(request()->all(), [
            'phone_email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $user = User::where('email', request()->phone_email)->orWhere('phone', request()->phone_email)->first();
        if (is_null($user)) {
            return ResponseFormatter::error(400, null, [
                'User tidak ditemukan',
            ]);
        }

        $userPassword = $user->password;
        if (Hash::check(request()->password, $userPassword)) {
            $token = $user->createToken(config('app.name'))->plainTextToken;

            return ResponseFormatter::success([
                'token' => $token,
            ]);
        }
        return ResponseFormatter::error(400, null, [
            'Password salah!',
        ]);
    }
}
