<?php

namespace App\Services;

use Illuminate\Support\Facades\Password;

class ResetPasswordService
{
    public function sendResetLinkEmail(string $email)
    {
        // Logika pengiriman email pengaturan ulang kata sandi
        $response = Password::sendResetLink(['email' => $email]);

        return [
            'message' => trans($response),
        ];
    }

    public function resetPassword(string $email, string $password, string $passwordConfirmation, string $token)
    {
        // Logika pengaturan ulang kata sandi
        $response = Password::reset(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $passwordConfirmation, 'token' => $token],
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
            }
        );

        return [
            'message' => trans($response),
        ];
    }
}