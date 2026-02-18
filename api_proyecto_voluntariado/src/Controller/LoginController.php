<?php

namespace App\Controller;

use App\Entity\Loginable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Security\UnifiedUserProvider;
use App\Service\Auth\AuthServiceInterface;
use App\Service\NotificationService;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

/**
 * S-1 + D-2: Controller dedicado exclusivamente a login y recuperación de contraseña.
 * Usa AuthServiceInterface (DIP) en lugar de llamar directamente a Firebase REST API.
 * Usa Loginable (OCP) en lugar de cadenas instanceof.
 */
#[Route('/api/auth', name: 'api_auth_')]
class LoginController extends AbstractController
{
    use ApiErrorTrait;

    public function __construct(
        private AuthServiceInterface $authService,
        private UnifiedUserProvider $unifiedUserProvider,
        private EmailServiceInterface $emailService
    ) {}

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->errorResponse('Email y contraseña requeridos', 400);
        }

        try {
            // D-2: Login delegado al servicio de autenticación (no a Firebase directamente)
            $authResult = $this->authService->signIn($email, $password);

            // Cargar usuario local
            try {
                $localUser = $this->unifiedUserProvider->loadUserByIdentifier($email);
            } catch (UserNotFoundException $e) {
                return $this->errorResponse('Inconsistencia de cuenta. El usuario existe en el sistema de autenticación pero no tiene perfil en la base de datos local.', 404);
            }

            if ($localUser instanceof \App\Security\User\SecurityUser) {
                $localUser = $localUser->getDomainUser();
            }

            // O-1: Login check polimórfico via Loginable
            if ($localUser instanceof Loginable && !$localUser->canLogin()) {
                return $this->errorResponse($localUser->getLoginDeniedReason(), 403);
            }

            return $this->json([
                'message' => 'Login correcto',
                'token' => $authResult->idToken,
                'refreshToken' => $authResult->refreshToken,
                'expiresIn' => $authResult->expiresIn,
                'localId' => $authResult->localId,
                'email' => $authResult->email,
                'emailVerified' => $authResult->emailVerified,
                'rol' => $this->getUserRole($localUser)
            ]);

        } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
            try {
                $errorContent = $e->getResponse()->toArray(false);
                $msg = $errorContent['error']['message'] ?? 'Error de autenticación';
            } catch (\Exception $decodeEx) {
                $msg = 'Error desconocido de autenticación';
            }
            if (in_array($msg, ['EMAIL_NOT_FOUND', 'INVALID_PASSWORD', 'INVALID_LOGIN_CREDENTIALS'])) {
                return $this->errorResponse('Credenciales inválidas (' . $msg . ')', 401);
            }
            return $this->errorResponse('Error de Firebase: ' . $msg, 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Error interno: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/login/google', name: 'login_google', methods: ['POST'])]
    public function loginGoogle(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return $this->errorResponse('Token de Firebase requerido', 400);
        }

        try {
            // D-2: Verificación delegada al servicio
            $authUser = $this->authService->verifyToken($token);

            if (!$authUser->email) {
                return $this->errorResponse('El token no contiene un email válido', 400);
            }

            try {
                $localUser = $this->unifiedUserProvider->loadUserByIdentifier($authUser->email);
            } catch (UserNotFoundException $e) {
                return $this->errorResponse('No existe una cuenta vinculada a este correo de Google. Por favor, regístrate primero.', 404, ['email' => $authUser->email, 'uid' => $authUser->uid]);
            }

            if ($localUser instanceof \App\Security\User\SecurityUser) {
                $localUser = $localUser->getDomainUser();
            }

            // O-1: Login check polimórfico via Loginable
            if ($localUser instanceof Loginable && !$localUser->canLogin()) {
                return $this->errorResponse($localUser->getLoginDeniedReason(), 403);
            }

            return $this->json([
                'message' => 'Login correcto (Google)',
                'token' => $token,
                'refreshToken' => null,
                'expiresIn' => 3600,
                'localId' => $authUser->uid,
                'email' => $authUser->email,
                'emailVerified' => $authUser->emailVerified,
                'rol' => $this->getUserRole($localUser)
            ]);

        } catch (FailedToVerifyToken $e) {
            return $this->errorResponse('Token inválido o expirado: ' . $e->getMessage(), 401);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error interno al verificar Google Login: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->errorResponse('Email es obligatorio', 400);
        }

        try {
            // D-2: Delegado al servicio
            $link = $this->authService->getPasswordResetLink($email);

            $this->emailService->sendEmail(
                $email,
                'Reset Password - Gestión Voluntariado',
                sprintf(
                    '<p>Has solicitado restablecer tu contraseña.</p><p>Haz clic aquí para cambiarla: <a href="%s">Restablecer Contraseña</a></p>', 
                    $link
                )
            );

            return $this->json(['message' => 'Si el correo existe, se ha enviado un enlace de recuperación.']);

        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
            return $this->json(['message' => 'Si el correo existe, se ha enviado un enlace de recuperación.']);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al procesar la solicitud: ' . $e->getMessage(), 500);
        }
    }

    private function getUserRole($user): string
    {
        if ($user instanceof \App\Entity\Administrador) return 'admin';
        if ($user instanceof \App\Entity\Voluntario) return 'voluntario';
        if ($user instanceof \App\Entity\Organizacion) return 'organizacion';
        return 'unknown';
    }
}
