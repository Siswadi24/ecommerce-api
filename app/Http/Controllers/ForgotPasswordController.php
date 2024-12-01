<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function requestOtp()
    {
        $validator = \validator(request()->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $check = DB::table('password_reset_tokens')->where('email', request()->email)->count();
        if ($check > 0) {
            return ResponseFormatter::error(400, null, [
                'Reset password telah dikirimkan, silahkan cek email anda atau Resend OTP',
            ]);
        }

        do {
            $genOTP = rand(100000, 999999);

            $otpCount = DB::table('password_reset_tokens')->where('token', $genOTP)->count();
        } while ($otpCount > 0);

        DB::table('password_reset_tokens')->insert([
            'email' => request()->email,
            'token' => $genOTP,
        ]);

        $user = User::whereEmail(request()->email)->firstOrFail();
        Mail::to($user->email)->send(new \App\Mail\SendForgotPasswordOtp($user, $genOTP));

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

        $otpRecord = DB::table('password_reset_tokens')->where('email', request()->email)->first();
        if (is_null($otpRecord)) {
            return ResponseFormatter::error(400, null, [
                'Reset password tidak ditemukan',
            ]);
        }

        $user = User::where('email', request()->email)->firstOrFail();

        do {
            $genOTP = rand(100000, 999999);

            $otpCount = DB::table('password_reset_tokens')->where('token', $genOTP)->count();
        } while ($otpCount > 0);

        DB::table('password_reset_tokens')->where('email', request()->email)->update([
            'token' => $genOTP,
        ]);

        Mail::to($user->email)->send(new \App\Mail\SendForgotPasswordOtp($user, $genOTP));

        return ResponseFormatter::success([
            'is_sent' => true,
        ]);
    }

    public function checkOtp()
    {
        $validator = \validator(request()->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|exists:password_reset_tokens,token',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $check = DB::table('password_reset_tokens')->where('token', request()->otp)->where('email', request()->email)->count();
        if ($check > 0) {
            return ResponseFormatter::success([
                'is_valid' => true,
            ]);
        }

        return ResponseFormatter::error(400, 'Invalid OTP');
    }

    public function resetPassword()
    {
        $validator = \validator(request()->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|exists:password_reset_tokens,token',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $tokenResetPassword = DB::table('password_reset_tokens')->where('token', request()->otp)->where('email', request()->email)->first();;
        if (!is_null($tokenResetPassword)) {
            $user = User::where('email', request()->email)->firstOrFail();
            $user->update([
                'password' => bcrypt(request()->password),
            ]);
            DB::table('password_reset_tokens')->where('token', request()->otp)->where('email', request()->email)->delete();

            $token = $user->createToken(config('app.name'))->plainTextToken;

            return ResponseFormatter::success([
                'token' => $token,
            ]);
        }
        return ResponseFormatter::error(400, 'Invalid OTP');
    }
}
