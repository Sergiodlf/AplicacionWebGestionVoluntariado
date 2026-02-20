<?php

namespace App\Service;

/**
 * Interfaz para centralizar todas las operaciones con el SDK de Firebase.
 * Permite desacoplar el código de dominio de la implementación de Kreait.
 */
interface FirebaseServiceInterface
{
    /**
     * Crea un usuario en Firebase Auth.
     * @return string El UID del usuario creado.
     */
    public function createUser(string $email, string $password, string $displayName): string;

    /**
     * Asigna claims personalizados (roles) a un usuario.
     */
    public function setUserRole(string $uid, string $role): void;

    /**
     * Obtiene un enlace para la verificación de email.
     */
    public function getEmailVerificationLink(string $email): string;

    /**
     * Obtiene un enlace para restablecer la contraseña.
     */
    public function getPasswordResetLink(string $email): string;

    /**
     * Envía una notificación push a un token específico.
     */
    public function sendPush(string $token, string $title, string $body, array $data = []): void;
    
    /**
     * Envía una notificación push a un tema (topic).
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void;

    /**
     * Suscribe un token a un tema.
     */
    public function subscribeToTopic(string $token, string $topic): void;

    /**
     * Obtiene el UID a partir de un email.
     */
    public function getUidByEmail(string $email): string;

    /**
     * Verifica un ID Token y retorna los claims.
     */
    public function verifyIdToken(string $token, int $leewaySeconds = 300): array;

    /**
     * Comprueba si el email del usuario está verificado.
     */
    public function isEmailVerified(string $uid): bool;

    /**
     * Marca manualmente el email de un usuario como verificado.
     */
    public function verifyEmail(string $uid): void;

    /**
     * Change a user password explicitly by an Admin using their email.
     */
    public function adminChangePassword(string $email, string $newPassword): void;

    /**
     * Crea o actualiza un usuario (pensado para fixtures).
     */
    public function syncUser(string $email, string $password, string $displayName, array $claims = []): string;
}
