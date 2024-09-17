<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        try {
            // Validasi input dari request

            // Cek apakah email sudah ada di database
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json(['status' => 'failed', 'error' => 'Email already exists.'], 409);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'no_telepon' => $request->no_telepon,
                'password' => Hash::make($request->password),
                'otp' => Str::random(6),
                'otp_expires_at' => now()->addMinute(30), // Sesuaikan dengan kadaluarsa yang diinginkan
            ]);

            Log::info('User created: ' . json_encode($user->toArray()));

            // Log the saved user details
            Log::info('User saved successfully after registration.');

            $this->sendOtpEmail($user);

            if ($user->otp_expires_at < now()) {
                // Tandai OTP sebagai kedaluwarsa dan tolak verifikasi
                $user->otp = null;
                $user->save();

                return response()->json(['status' => 'failed', 'message' => 'Invalid OTP or expired.'], 401);
            }

            $expiresAt = $user->otp_expires_at->toIso8601String(); // Format waktu sesuai kebutuhan

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful. OTP sent to ' . $user->email,
                'email' => $user->email,
                'expires_at' => $expiresAt,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Tangkap kesalahan terkait database, misal koneksi database gagal
            Log::error('Database error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error, please try again later.'], 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tangkap kesalahan validasi
            Log::warning('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['error' => 'Invalid input.', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Tangkap kesalahan umum lainnya
            Log::error('Unexpected error: ' . $e->getMessage());
            return response()->json(['error' => 'Unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function login(Request $request)
    {
        // Validasi input dari request

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'error' => 'Invalid email or password.'
            ], 401);
        }

        // Generate dan kirim OTP baru
        $otp = Str::random(6);
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinute(30); // Sesuaikan dengan kadaluarsa yang dinginkan 

        // Check if the user is saved successfully
        if ($user->save()) {
            // Log the saved user details
            Log::info('User logged in successfully: ' . json_encode($user->toArray()));

            $this->sendOtpEmail($user);

            $expiresAt = $user->otp_expires_at->toIso8601String(); // Format waktu sesuai kebutuhan

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful. OTP sent to ' . $user->email,
                'email' => $user->email,
                'expires_at' => $expiresAt,
            ]);
        } else {
            // Log if there's an issue with saving
            Log::error('Failed to log in user: ' . json_encode($user->toArray()));

            return response()->json([
                'status' => 'error',
                'error' => 'An error occurred while processing the request.'
            ], 500);
        }
    }

    // Metode untuk mengirimkan email OTP
    protected function sendOtpEmail($user)
    {
        $otp = $user->otp;

        Mail::raw("Your OTP is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('OTP for Authentication');
        });
    }

    public function verifyOtp(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp !== $request->otp) {
            // Tambahkan output debug atau log
            Log::error("Invalid OTP - User: " . ($user ? $user->id : 'null') . ", Entered OTP: " . $request->otp);

            return response()->json(['error' => 'Invalid OTP.'], 401);
        }
        // Pemeriksaan waktu kadaluarsa sebelum verifikasi OTP
        if ($user->otp_expires_at < now()) {
            // Tandai OTP sebagai kedaluwarsa dan tolak verifikasi
            $user->otp = null;
            $user->save();

            return response()->json(['error' => 'OTP expired..'], 401);
        }

        // Proses verifikasi OTP
        $token = $user->createToken('otp-token')->plainTextToken;

        // Clear OTP setelah verifikasi
        $user->otp = null;
        $user->save();

        return response()->json(['token' => $token, 'message' => 'OTP verified.']);
    }

    public function resendOtp(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        // Generate OTP baru dan perbarui waktu kadaluarsa
        $user->otp = Str::random(6);
        $user->otp_expires_at = now()->addMinute(30); // Sesuaikan dengan kadaluarsa yang diinginkan

        if ($user->save()) {
            // Log the saved user details
            Log::info('OTP resent to user: ' . json_encode($user->toArray()));

            $this->sendOtpEmail($user);

            $expiresAt = $user->otp_expires_at->toIso8601String(); // Format waktu sesuai kebutuhan

            return response()->json([
                'status' => 'success',
                'message' => 'OTP resent to ' . $user->email,
                'email' => $user->email,
                'expires_at' => $expiresAt,
            ]);
        } else {
            // Log if there's an issue with saving
            Log::error('Failed to resend OTP to user: ' . json_encode($user->toArray()));

            return response()->json([
                'status' => 'error',
                'error' => 'An error occurred while processing the request.'
            ], 500);
        }
    }
}
