<?php

namespace App\Services;

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class ResetPasswordService
{
    public function sendResetLinkEmail($data)
    {
        // Logika pengiriman email pengaturan ulang kata sandi
        // $response = Password::sendResetLink(['email' => $data->email]);
        $verificationUrl = "http://localhost:5173/resetPassword?email={$data->email}&token={$data->token}";

        Mail::raw("Your link for reset password is: $verificationUrl", function ($message) use ($data) {
            $message->to($data->email)
                ->subject('Reset Link');
        });
        return [
            'status' => "sukses",
            'message' => "sukses kirim",
        ];
    }

    public function resetPassword(string $email, string $password, string $passwordConfirmation, string $token)
    {
        // Mencari entri token dari database berdasarkan email
        $passwordReset = PasswordReset::where('email', $email)->first();

        // Jika token tidak ditemukan atau email tidak ada di database
        if (!$passwordReset) {
            return [
                'message' => 'Email tidak ditemukan atau token tidak valid.',
            ];
        }

        Log::info("passwordReset " . json_encode($passwordReset->toArray()));

        // Memverifikasi apakah token yang diberikan cocok dengan yang ada di database
        // if (!Hash::check($token, $passwordReset->token)) { // pengecekan jika di encryp
        if ($token !== $passwordReset->token) {
            return [
                'message' => 'Token tidak valid.',
            ];
        }

        // Memastikan password dan password confirmation cocok
        if ($password !== $passwordConfirmation) {
            return [
                'message' => 'Password dan konfirmasi password tidak cocok.',
            ];
        }
        // Menemukan user berdasarkan email
        $user = User::where('email', $email)->first();

        // Jika user tidak ditemukan
        if (!$user) {
            return [
                'message' => 'User tidak ditemukan.',
            ];
        }

        // Mengupdate password user
        $user->password = Hash::make($password);
        $user->save();
        // Menghapus token setelah password direset
        $passwordReset->delete();

        return [
            'message' => 'Password berhasil direset.',
        ];
    }
}
