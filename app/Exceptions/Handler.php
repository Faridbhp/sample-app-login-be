<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception): Response
    {
        // Mengembalikan JSON jika permintaan mengharapkan JSON
        if ($request->expectsJson()) {
            // Menangani kesalahan validasi
            if ($exception instanceof ValidationException) {
                return response()->json($exception->errors(), 422);
            }

            // Menangani kesalahan umum
            return response()->json(['error' => 'Unexpected error occurred.'], 500);
        }

        // Jika bukan permintaan JSON, gunakan perilaku default
        return parent::render($request, $exception);
    }
}
