<?php

namespace App\Service\Auth;

use App\Service\FirebaseServiceInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FirebaseAuthService implements AuthServiceInterface
{
    public function __construct(
        private FirebaseServiceInterface $firebaseService,
        private HttpClientInterface $httpClient
    ) {}

    public function verifyToken(string $token): AuthUserDTO
    {
        try {
            $claims = $this->firebaseService->verifyIdToken($token);
            
            $uid = $claims['sub'] ?? null;
            $email = $claims['email'] ?? null;
            $emailVerified = $claims['email_verified'] ?? false;
            
            if (empty($email)) {
                throw new CustomUserMessageAuthenticationException('The access token does not contain an email claim.');
            }

            return new AuthUserDTO(
                uid: $uid,
                email: (string) $email,
                emailVerified: (bool) $emailVerified,
                claims: $claims
            );

        } catch (\Throwable $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage());
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

        // Get email verification status via centralized service
        $emailVerified = $this->firebaseService->isEmailVerified($firebaseData['localId']);

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
        return $this->firebaseService->getPasswordResetLink($email);
    }

    public function changePassword(string $email, string $oldPassword, string $newPassword): void
    {
        // 1. Verify old password by signing in
        $authResult = $this->signIn($email, $oldPassword);
        $idToken = $authResult->idToken;

        // 2. Update password
        $apiKey = $_ENV['FIREBASE_API_KEY'] ?? $_SERVER['FIREBASE_API_KEY'] ?? getenv('FIREBASE_API_KEY');
        
        $response = $this->httpClient->request('POST', 'https://identitytoolkit.googleapis.com/v1/accounts:update?key=' . $apiKey, [
            'json' => [
                'idToken' => $idToken,
                'password' => $newPassword,
                'returnSecureToken' => false
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to update password in Firebase.');
        }
    }
}
