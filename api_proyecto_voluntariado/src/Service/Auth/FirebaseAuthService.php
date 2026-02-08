<?php

namespace App\Service\Auth;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class FirebaseAuthService implements AuthServiceInterface
{
    public function __construct(
        private Auth $firebaseAuth
    ) {}

    public function verifyToken(string $token): AuthUserDTO
    {
        try {
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($token);
            $claims = $verifiedIdToken->claims();
            
            $uid = $claims->get('sub');
            $email = (string) $claims->get('email');
            $emailVerified = (bool) $claims->get('email_verified');
            
            if (empty($email)) {
                throw new CustomUserMessageAuthenticationException('The access token does not contain an email claim.');
            }

            return new AuthUserDTO(
                uid: $uid,
                email: $email,
                emailVerified: $emailVerified,
                claims: $claims->all()
            );

        } catch (FailedToVerifyToken $e) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired Firebase token: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new CustomUserMessageAuthenticationException('Authentication error: ' . $e->getMessage());
        }
    }
}
