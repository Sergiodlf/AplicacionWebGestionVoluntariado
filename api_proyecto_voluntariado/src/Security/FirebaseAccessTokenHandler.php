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
            // Verify the token with Firebase
            // This verifies signature, expiration, and format
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($accessToken);
            
            // Extract the unique identifier (email, in your project logic)
            $email = $verifiedIdToken->claims()->get('email');

            if (!$email) {
                throw new BadCredentialsException('The access token does not contain an email claim.');
            }

            // Return a UserBadge. The UnifiedUserProvider will use this email
            // to load the corresponding Voluntario or Organizacion.
            return new UserBadge($email);

        } catch (FailedToVerifyToken $e) {
            throw new BadCredentialsException('Invalid or expired Firebase token: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new BadCredentialsException('An error occurred during token verification.');
        }
    }
}
