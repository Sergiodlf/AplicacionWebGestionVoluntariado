<?php

namespace App\Service\Auth;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

interface AuthServiceInterface
{
    /**
     * Verifies a token and returns user data.
     * 
     * @throws AuthenticationException
     */
    public function verifyToken(string $token): AuthUserDTO;
}
