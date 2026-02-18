<?php

namespace App\Service;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;

/**
 * Implementación centralizada del servicio de Firebase.
 * Única clase autorizada a importar el SDK de Kreait (fuera de Auth).
 */
class FirebaseService implements FirebaseServiceInterface
{
    public function __construct(
        private Auth $auth,
        private Messaging $messaging,
        private LoggerInterface $logger
    ) {}

    public function createUser(string $email, string $password, string $displayName): string
    {
        try {
            $user = $this->auth->createUser([
                'email' => $email,
                'emailVerified' => false,
                'password' => $password,
                'displayName' => $displayName,
                'disabled' => false,
            ]);
            return $user->uid;
        } catch (\Kreait\Firebase\Exception\Auth\EmailExists $e) {
            // Si ya existe, intentamos obtener el UID (Idempotencia)
            return $this->getUidByEmail($email);
        } catch (\Throwable $e) {
            $this->logger->error('FirebaseService: Error creating user.', ['error' => $e->getMessage(), 'email' => $email]);
            throw new \RuntimeException('No se pudo crear el usuario en el sistema de autenticación: ' . $e->getMessage());
        }
    }

    public function setUserRole(string $uid, string $role): void
    {
        try {
            $this->auth->setCustomUserClaims($uid, ['rol' => $role]);
        } catch (\Throwable $e) {
            $this->logger->warning('FirebaseService: Error setting claims.', ['uid' => $uid, 'role' => $role, 'error' => $e->getMessage()]);
        }
    }

    public function getEmailVerificationLink(string $email): string
    {
        try {
            return $this->auth->getEmailVerificationLink($email);
        } catch (\Throwable $e) {
            $this->logger->error('FirebaseService: Error generating verification link.', ['email' => $email, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Error al generar el enlace de verificación.');
        }
    }

    public function getPasswordResetLink(string $email): string
    {
        try {
            return $this->auth->getPasswordResetLink($email);
        } catch (\Throwable $e) {
            $this->logger->error('FirebaseService: Error generating reset link.', ['email' => $email, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Error al generar el enlace de recuperación.');
        }
    }

    public function sendPush(string $token, string $title, string $body, array $data = []): void
    {
        if (empty($token)) return;

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);
        } catch (\Throwable $e) {
            $this->logger->error('FirebaseService: Error sending push.', ['error' => $e->getMessage()]);
        }
    }

    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);
        } catch (\Throwable $e) {
            $this->logger->error('FirebaseService: Error sending to topic.', ['topic' => $topic, 'error' => $e->getMessage()]);
        }
    }

    public function subscribeToTopic(string $token, string $topic): void
    {
        try {
            $this->messaging->subscribeToTopic($topic, $token);
        } catch (\Throwable $e) {
            $this->logger->error('FirebaseService: Error subscribing to topic.', ['topic' => $topic, 'error' => $e->getMessage()]);
        }
    }

    public function getUidByEmail(string $email): string
    {
        try {
            $user = $this->auth->getUserByEmail($email);
            return $user->uid;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Usuario no encontrado en el sistema de autenticación.');
        }
    }

    public function verifyIdToken(string $token): array
    {
        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            return $verifiedIdToken->claims()->all();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Token de Firebase inválido: ' . $e->getMessage());
        }
    }

    public function isEmailVerified(string $uid): bool
    {
        try {
            $user = $this->auth->getUser($uid);
            return $user->emailVerified;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function verifyEmail(string $uid): void
    {
        try {
            $this->auth->updateUser($uid, ['emailVerified' => true]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('No se pudo verificar el email: ' . $e->getMessage());
        }
    }

    public function syncUser(string $email, string $password, string $displayName, array $claims = []): string
    {
        try {
            try {
                $user = $this->auth->getUserByEmail($email);
                $uid = $user->uid;
                $this->auth->updateUser($uid, [
                    'password' => $password,
                    'emailVerified' => true,
                    'displayName' => $displayName
                ]);
            } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                $user = $this->auth->createUser([
                    'email' => $email,
                    'password' => $password,
                    'emailVerified' => true,
                    'displayName' => $displayName
                ]);
                $uid = $user->uid;
            }
            if (!empty($claims)) {
                $this->auth->setCustomUserClaims($uid, $claims);
            }
            return $uid;
        } catch (\Throwable $e) {
            $this->logger->error('FirebaseService: Error syncing user.', ['error' => $e->getMessage(), 'email' => $email]);
            throw new \RuntimeException('No se pudo sincronizar el usuario: ' . $e->getMessage());
        }
    }
}
