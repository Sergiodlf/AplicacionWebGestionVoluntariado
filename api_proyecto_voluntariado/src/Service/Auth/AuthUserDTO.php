<?php

namespace App\Service\Auth;

class AuthUserDTO
{
    public function __construct(
        public string $uid,
        public string $email,
        public bool $emailVerified,
        public array $claims = []
    ) {}
}
