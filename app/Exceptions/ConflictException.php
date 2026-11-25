<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat terjadi konflik bisnis (HTTP 409), misalnya pengajuan sudah
 * diputuskan, transisi status tidak diizinkan, atau unit masih dirujuk data lain.
 */
class ConflictException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = []
    ) {
        parent::__construct($message, 409);
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
