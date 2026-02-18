<?php

namespace App\Service\Auth;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FirebaseAuthService implements AuthServiceInterface
{
    public function __construct(
        private Auth $firebaseAuth,
        private HttpClientInterface $httpClient
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

    public function signIn(string $email, string $password): AuthResultDTO
    {
        $apiKey = $_ENV['FIREBASE_API_KEY'] ?? $_SERVER['FIREBASE_API_KEY'] ?? getenv('FIREBASE_API_KEY');
        
        if (!$apiKey) {
            throw new \RuntimeException('Error de configuración en servidor: Falta FIREBASE_API_KEY');
        }

        $response = $this->httpClient->request('POST', 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . $apiKey, [
            'json' => [
                'email' => $email,
                'password' => $password,
                'returnSecureToken' => true
            ],
            'headers' => [
                'Referer' => 'https://proyecto-voluntariado-9c2d5.firebaseapp.com'
            ]
        ]);

        $firebaseData = $response->toArray();

        // Get email verification status
        $emailVerified = false;
        try {
            $firebaseUser = $this->firebaseAuth->getUser($firebaseData['localId']);
            $emailVerified = $firebaseUser->emailVerified;
        } catch (\Exception $e) {
            // Don't break login if verification check fails
        }

        return new AuthResultDTO(
            idToken: $firebaseData['idToken'],
            refreshToken: $firebaseData['refreshToken'] ?? null,
            expiresIn: $firebaseData['expiresIn'],
            localId: $firebaseData['localId'],
            email: $firebaseData['email'],
            emailVerified: $emailVerified
        );
    }

    public function getPasswordResetLink(string $email): string
    {
        return $this->firebaseAuth->getPasswordResetLink($email);
    }
}
