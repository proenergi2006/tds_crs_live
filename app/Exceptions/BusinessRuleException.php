<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class BusinessRuleException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $status = 409, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->status);
    }

    public function report(): bool
    {
        if ($this->getPrevious() !== null) {
            report($this->getPrevious());
        }

        return true;
    }
}
