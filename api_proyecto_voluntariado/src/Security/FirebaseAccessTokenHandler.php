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
        private Auth $firebaseAuth,
        private UnifiedUserProvider $userProvider
    ) {}

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $log = "--- Firewall Token Check ---\n";
        try {
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($accessToken);
            $claims = $verifiedIdToken->claims();
            
            $email = $claims->get('email'); // Should be a string
            $email = (string)$email; // Force string to satisfy type hint if needed

            $log .= "Email from token: '$email'\n";

            if (!$email) {
                // Log and throw
                file_put_contents('debug_firewall.txt', $log . "Error: No email claim.\n", FILE_APPEND);
                throw new BadCredentialsException('The access token does not contain an email claim.');
            }
            
            // 1. INTENTO DE CARGA DESDE DB (Prioridad: Voluntario / Organización Real)
            try {
                $dbUser = $this->userProvider->loadUserByIdentifier($email);
                file_put_contents('debug_firewall.txt', $log . "User found in DB. Returning Badge.\n", FILE_APPEND);
                return new UserBadge($email, fn() => $dbUser);
            } catch (\Symfony\Component\Security\Core\Exception\UserNotFoundException $e) {
                $log .= "User NOT found in DB.\n";
            }

            // --- DETECCION DE ROL PARA SUPERADMIN EN MEMORIA ---
            $roles = [];
            
            // Opción A: Claim 'admin' (boolean)
            if ($claims->has('admin') && $claims->get('admin') === true) {
                $roles[] = 'ROLE_ADMIN';
                $log .= "Claim 'admin' found.\n";
            }
            // Opción B: Claim 'rol' o 'role' (string)
            if ($claims->has('rol') && $claims->get('rol') === 'admin') {
                $roles[] = 'ROLE_ADMIN';
                $log .= "Claim 'rol'='admin' found.\n";
            }
            // Opción C: Email específico (Backdoor temporal)
            if ($email === 'admin@admin.com' || $email === 'adminTest5666@gmail.com') {
                $roles[] = 'ROLE_ADMIN';
                $log .= "Email whitelist matched.\n";
            }

            // --- USER LOADER ---
            // Si es ADMIN y no estaba en DB, cargamos el usuario en memoria
            if (in_array('ROLE_ADMIN', $roles)) {
                 file_put_contents('debug_firewall.txt', $log . "User is ADMIN (Memory). Returning Badge.\n", FILE_APPEND);
                return new UserBadge($email, function() use ($email, $roles) {
                    return new \App\Security\User\AdminUser($email, $roles);
                });
            }

            // Si no es admin y no está en DB, devolvemos un Badge estándar que fallará
            // cuando Symfony intente cargar el usuario (o lanzamos error aquí)
            file_put_contents('debug_firewall.txt', $log . "Error: User not in DB and not Admin. Denying.\n", FILE_APPEND);
            throw new BadCredentialsException('Usuario no registrado en el sistema.');

        } catch (FailedToVerifyToken $e) {
             file_put_contents('debug_firewall.txt', $log . "Error: Validate Token Failed: " . $e->getMessage() . "\n", FILE_APPEND);
            throw new BadCredentialsException('Invalid or expired Firebase token: ' . $e->getMessage());
        } catch (\Throwable $e) {
             file_put_contents('debug_firewall.txt', $log . "Error: General Exception: " . $e->getMessage() . "\n", FILE_APPEND);
            throw new BadCredentialsException('An error occurred during token verification: ' . $e->getMessage());
        }
    }
}
