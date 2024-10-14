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
            'status' => "success",
            'message' => "Email reset password sudah dikirim ke email anda",
        ];
    }
}
