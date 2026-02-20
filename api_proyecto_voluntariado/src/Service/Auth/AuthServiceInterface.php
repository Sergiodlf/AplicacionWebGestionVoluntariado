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

    /**
     * Signs in a user with email and password.
     * 
     * @throws \Exception If credentials are invalid or service unavailable.
     */
    public function signIn(string $email, string $password): AuthResultDTO;

    /**
     * Generates a password reset link for the given email.
     * 
     * @throws \Exception If the user is not found or service fails.
     */
    public function getPasswordResetLink(string $email): string;

    /**
     * Changes the password for a user.
     * 
     * @throws \Exception If authentication fails or update fails.
     */
    public function changePassword(string $email, string $oldPassword, string $newPassword): void;

    /**
     * Refreshes an ID token using a refresh token.
     * 
     * @throws \Exception If the refresh token is invalid or expired.
     */
    public function refreshIdToken(string $refreshToken): AuthResultDTO;
}
