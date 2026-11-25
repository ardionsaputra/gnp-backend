<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat aturan bisnis / validasi domain tidak terpenuhi (HTTP 422),
 * misalnya jadwal sewa bentrok atau unit sedang tidak tersedia.
 */
class BusinessRuleException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = []
    ) {
        parent::__construct($message, 422);
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
