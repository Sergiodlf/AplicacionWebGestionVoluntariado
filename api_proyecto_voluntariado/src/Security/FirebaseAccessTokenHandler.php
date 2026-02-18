<?php

namespace App\Security;

use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class FirebaseAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private \App\Service\Auth\AuthServiceInterface $authService,
        private UnifiedUserProvider $userProvider
    ) {}

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        try {
            // 1. Verify Token via Service
            $authUser = $this->authService->verifyToken($accessToken);

            // 2. Check Email Verification
            if (!$authUser->emailVerified) {
                // throw new \Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException('Debes verificar tu correo electrónico antes de iniciar sesión.');
            }

            // 3. Load User (Wrapped in SecurityUser) via Provider
            return new UserBadge($authUser->email, function() use ($authUser) {
                try {
                    return $this->userProvider->loadUserByIdentifier($authUser->email);
                } catch (\Symfony\Component\Security\Core\Exception\UserNotFoundException $e) {
                     // Auto-Admin Check based on Claims (if not in DB)
                     if (in_array('admin', $authUser->claims) || ($authUser->claims['rol'] ?? '') === 'admin') {
                        return new \App\Security\User\SecurityUser($authUser->email, ['ROLE_ADMIN'], null);
                     }
                     throw $e;
                }
            });

        } catch (\Exception $e) {
            throw new BadCredentialsException($e->getMessage());
        }
    }
}
