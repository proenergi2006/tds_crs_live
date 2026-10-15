<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (AuthenticationException $e, Request $request) {
            // reason expiry dititipkan lewat request attributes karena callback Sanctum gak nerima $request
            $reason = $request->attributes->get('token_expired_reason');

            if ($reason) {
                return response()->json(['message' => 'Unauthenticated.', 'reason' => $reason], 401);
            }
        });
    }

    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);

        if (! $this->wantsJsonEnvelope($request)
            || ! $response instanceof JsonResponse
            || $response->getStatusCode() < 400) {
            return $response;
        }

        $data = $response->getData(true);

        if (! is_array($data) || array_key_exists('success', $data)) {
            return $response;
        }

        return $response->setData(['success' => false, ...$data]);
    }

    private function wantsJsonEnvelope(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }
}
