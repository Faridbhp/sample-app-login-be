<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Services\ResetPasswordService;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    //
    protected $resetPasswordService;

    public function __construct(ResetPasswordService $resetPasswordService)
    {
        $this->resetPasswordService = $resetPasswordService;
    }

    public function sendResetLinkEmail(ForgotPasswordRequest $request): JsonResponse
    {
        $response = $this->resetPasswordService->sendResetLinkEmail($request->email);

        return response()->json($response);
    }
}
