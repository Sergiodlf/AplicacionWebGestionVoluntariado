<?php

namespace App\Service\Auth;

/**
 * DTO para el resultado de una operación de sign-in.
 */
class AuthResultDTO
{
    public function __construct(
        public string $idToken,
        public ?string $refreshToken,
        public string $expiresIn,
        public string $localId,
        public string $email,
        public bool $emailVerified = false
    ) {}
}
