<?php

namespace App\Exceptions;

use RuntimeException;

class AttendanceException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 400,
        public readonly array $payload = [],
    ) {
        parent::__construct($message);
    }

    public function toResponse()
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            ...$this->payload,
        ], $this->httpStatus);
    }
}
