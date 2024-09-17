<?php

namespace App\Responses;

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\ResetPasswordViewResponse as ResetPasswordViewResponseContract;

class ResetPasswordViewResponse implements ResetPasswordViewResponseContract
{
    /**
     * Display the reset password view.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function toResponse($request)
    {
        return view('auth.reset-password', ['request' => $request])->with('token', $request->route('token'));
    }
}
