<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        try {
            // Log semua input untuk memastikan input diterima
            Log::info('Request received: ' . json_encode($request->all()));

            // Tentukan field yang valid
            $validFields = [
                'name',
                'email',
                'no_telepon',
                'password',
            ];

            // Periksa jika ada field yang tidak diizinkan
            $inputFields = array_keys($request->all());
            $invalidFields = array_diff($inputFields, $validFields);

            // Jika ada field yang tidak valid, kembalikan pesan error
            if (!empty($invalidFields)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid fields provided.',
                    'invalid_fields' => $invalidFields
                ], 400);
            }

            // Validasi input dari request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'no_telepon' => 'required|string|max:15',
                'password' => 'required|string|min:8',
            ]);

            // Jika validasi gagal, kembalikan pesan error
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cek apakah email sudah ada di database
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json(['status' => 'error', 'message' => 'Email already exists.'], 409);
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

            $this->sendRegisterTokenEmail($user);

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
            Log::warning('Validation error: ' . json_encode($e->errors()));
            return response()->json(['error' => 'Invalid input.'], 422);
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
                'message' => 'Invalid email or password.'
            ]);
        }

        // lakukan pemeriksaan email_verified_at === null
        if ($user->email_verified_at === null) {
            // Pemeriksaan waktu kadaluarsa sebelum verifikasi OTP
            if ($user->otp_expires_at < now()) {
                // Generate dan kirim OTP baru
                $otp = Str::random(6);
                $user->otp = $otp;
                $user->otp_expires_at = now()->addMinute(30); // Sesuaikan dengan kadaluarsa yang dinginkan 
                $user->save();

                $this->sendRegisterTokenEmail($user);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Verifikasi akun anda terlebih dahulu, link verifikasi dikirim ke email anda'
            ]);
        }

        // Hapus token lama
        $user->tokens()->delete();

        // Buat personal access token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Log the saved user details
        Log::info('User logged in successfully: ' . json_encode($user->toArray()));

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'email' => $user->email,
            'token' => $token
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        Log::info('Mencari  $user dengan email: ' . $user);
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
        $user->email_verified_at = now();
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
            Log::error('error to resend OTP to user: ' . json_encode($user->toArray()));

            return response()->json([
                'status' => 'error',
                'error' => 'An error occurred while processing the request.'
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            // Log semua input untuk memastikan input diterima
            Log::info('Request received: ' . json_encode($request->all()));

            // Mencari entri token dari database berdasarkan email
            $passwordReset = PasswordReset::where('email', $request->email)->first();
            // Jika token tidak ditemukan atau email tidak ada di database
            if (!$passwordReset) {
                return [
                    'message' => 'Email tidak ditemukan atau token tidak valid.',
                    'status' => 'error',
                ];
            }
            Log::info("passwordReset " . json_encode($passwordReset->toArray()));

            // Memverifikasi apakah token yang diberikan cocok dengan yang ada di database
            // if (!Hash::check($token, $passwordReset->token)) { // pengecekan jika di encryp
            if ($request->token !== $passwordReset->token) {
                return [
                    'message' => 'Token tidak valid.',
                    'status' => 'error',
                ];
            }

            // Memastikan password dan password confirmation cocok
            if ($request->password !== $request->confirmPassword) {
                return [
                    'message' => 'Password dan konfirmasi password tidak cocok.',
                    'status' => 'error',
                ];
            }

            // Menemukan user berdasarkan email
            $user = User::where('email', $request->email)->first();

            // Jika user tidak ditemukan
            if (!$user) {
                return [
                    'message' => 'User tidak ditemukan.',
                    'status' => 'error',
                ];
            }

            // Mengupdate password user
            $user->password = Hash::make($request->password);
            $user->save();
            // Menghapus token setelah password direset
            $passwordReset->delete();

            return [
                'message' => 'Password berhasil direset.',
                'status' => 'success',
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            // Tangkap kesalahan terkait database, misal koneksi database gagal
            Log::error('Database error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error, please try again later.'], 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tangkap kesalahan validasi
            Log::warning('Validation error: ' . json_encode($e->errors()));
            return response()->json(['error' => 'Invalid input.'], 422);
        } catch (\Exception $e) {
            // Tangkap kesalahan umum lainnya
            Log::error('Unexpected error: ' . $e->getMessage());
            return response()->json(['error' => 'Unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function changePassword(Request $request)
    {
        Log::info("requs data" . $request);
        // // Validasi input
        // Tentukan field yang valid
        $validFields = [
            'old_password',
            'new_password',
            'confirm_password',
        ];

        // Periksa jika ada field yang tidak diizinkan
        $inputFields = array_keys($request->all());
        $invalidFields = array_diff($inputFields, $validFields);

        // Jika ada field yang tidak valid, kembalikan pesan error
        if (!empty($invalidFields)) {
            $errors = array_values($invalidFields);
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid fields provided.',
                'invalid_fields' => $errors
            ]);
        }

        // Validasi input dari request
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|string|min:8|same:new_password',
        ]);

        // Jika validasi gagal, kembalikan pesan error
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $errors,
            ]);
        }

        $user = $request->user();
        Log::info("data user" . $user);

        // cek password lama apakah sama dengan yang tersimpan di db
        if (!$user || !Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password lama tidak valid.'
            ]);
        }

        $user->password = $request->new_password;
        $user->save();
        // Kembalikan respon sukses
        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully.'
        ]);
    }

    protected function sendRegisterTokenEmail($user)
    {
        $otp = $user->otp;

        // Membuat link verifikasi email
        $verificationUrl = "http://localhost:5173/login?email={$user->email}&token={$otp}";


        Mail::raw("Your link for email verification is: $verificationUrl", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Email Verification Link');
        });
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
}
