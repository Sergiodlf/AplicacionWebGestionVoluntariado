<?php

namespace App\Security;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class FirebaseAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private Auth $firebaseAuth
    ) {}

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        try {
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($accessToken);
            $claims = $verifiedIdToken->claims();
            
            $email = $claims->get('email');
            
            // --- DETECCION DE ROL (Claims personalizados o email hardcoded) ---
            $roles = [];
            // Opción A: Claim 'admin' (boolean)
            if ($claims->has('admin') && $claims->get('admin') === true) {
                $roles[] = 'ROLE_ADMIN';
            }
            // Opción B: Claim 'rol' o 'role' (string)
            if ($claims->has('rol') && $claims->get('rol') === 'admin') {
                $roles[] = 'ROLE_ADMIN';
            }
            // Opción C: Email específico (Backdoor temporal)
            if ($email === 'admin@admin.com') {
                $roles[] = 'ROLE_ADMIN';
            }

            if (!$email) {
                throw new BadCredentialsException('The access token does not contain an email claim.');
            }

            // --- USER LOADER ---
            // Si es ADMIN, cargamos el usuario en memoria (sin DB)
            if (in_array('ROLE_ADMIN', $roles)) {
                return new UserBadge($email, function() use ($email, $roles) {
                    return new \App\Security\User\AdminUser($email, $roles);
                });
            }

            // Si es usuario normal, delegamos al UnifiedUserProvider estándar
            return new UserBadge($email);

        } catch (FailedToVerifyToken $e) {
            throw new BadCredentialsException('Invalid or expired Firebase token: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new BadCredentialsException('An error occurred during token verification: ' . $e->getMessage());
        }
    }
}
