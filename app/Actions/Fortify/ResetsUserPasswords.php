<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\PasswordBroker;
use Laravel\Fortify\Contracts\ResetsUserPasswords as ResetsUserPasswordsContract;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Log;

class ResetsUserPasswords implements ResetsUserPasswordsContract
{
    /**
     * Reset the given user's password.
     *
     * @param  mixed  $user
     * @param  array|string  $password
     * @return void
     */
    public function reset($user, $password)
    {
        // Ensure $password is a string
        if (is_array($password)) {
            $password = $password['password'] ?? null;
        }

        if (!is_string($password)) {
            // Handle error or throw an exception as needed
            // For example, you can log an error and return
            // Alternatively, you can throw an exception
            // For now, we'll log an error and return
            Log::error('Invalid password format during password reset.');

            return;
        }

        // Your password reset logic here
        $user->forceFill([
            'password' => Hash::make($password),
        ])->save();
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return PasswordBroker
     */
    public function broker()
    {
        return app(PasswordBroker::class);
    }

    /**
     * Get the password reset options for the broker.
     *
     * @return array
     */
    public function brokerOptions()
    {
        return [
            //
        ];
    }
}
