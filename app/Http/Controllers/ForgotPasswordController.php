<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Services\ResetPasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    //
    protected $resetPasswordService;

    public function __construct(ResetPasswordService $resetPasswordService)
    {
        $this->resetPasswordService = $resetPasswordService;
    }

    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        try {
            // Validasi email kosong
            if (empty($request->email)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email wajib diisi.',
                ]); // 400 Bad Request
            }
            // Validasi format email
            if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Format email tidak valid.',
                ]); // 400 Bad Request
            }

            // Mengambil user berdasarkan email
            $user = User::where('email', $request->email)->first();

            // Jika user tidak ditemukan, pesan akan dihasilkan dari validasi
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email tidak ditemukan!',
                ]);  // Gunakan 404 untuk email tidak ditemukan
            }

            // Buat token reset password
            $token = Str::random(60);
            // Simpan token ke dalam tabel password_resets atau tabel yang Anda buat
            $createResetPassword = PasswordReset::updateOrCreate(
                ['email' => $request->email], // Kondisi untuk menemukan entri yang ada
                [
                    'token' => $token,
                    'created_at' => now(),
                ]
            );

            Log::info('createResetPassword: ' . json_encode($createResetPassword->toArray()));

            $response = $this->resetPasswordService->sendResetLinkEmail($createResetPassword);
            return response()->json($response);
        } catch (\Exception $e) {
            // Menangani exception yang tidak terduga
            Log::error('Unexpected error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan, silakan coba lagi.',
            ]);
        }
    }
}
