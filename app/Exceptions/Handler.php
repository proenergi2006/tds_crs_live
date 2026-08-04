<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // reason expiry dititipkan lewat request attributes karena callback Sanctum gak nerima $request
        $this->renderable(function (AuthenticationException $e, Request $request) {
            $reason = $request->attributes->get('token_expired_reason');

            if ($reason) {
                return response()->json(['message' => 'Unauthenticated.', 'reason' => $reason], 401);
            }
        });
    }
}
