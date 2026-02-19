<?php

namespace App\Security;

use App\Service\Auth\AuthServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use App\Security\User\User;

/**
 * Maneja la autenticación mediante Access Tokens de Firebase.
 * 
 * Este handler intercepta el token Bearer, lo valida contra Firebase
 * y recupera el usuario correspondiente de la base de datos local.
 */
class FirebaseAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private AuthServiceInterface $authService,
        private UnifiedUserProvider $userProvider,
        private LoggerInterface $logger
    ) {}

    /**
     * Valida el token y retorna un UserBadge con el identificador del usuario.
     * 
     * @throws BadCredentialsException Si el token es inválido o el usuario no existe.
     */
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        try {
            // 1. Verificar el Token a través del servicio de autenticación
            // Esto comprueba la firma y la fecha de expiración via Firebase
            $authUser = $this->authService->verifyToken($accessToken);

            // 2. Crear el UserBadge para que Symfony cargue el usuario
            // Se usa una lazy-callback para cargar el usuario bajo demanda
            return new UserBadge($authUser->email, function() use ($authUser) {
                try {
                    // Intentar cargar el usuario local desde el UnifiedUserProvider
                    return $this->userProvider->loadUserByIdentifier($authUser->email);

                } catch (UserNotFoundException $e) {
                    // LOGIC: Si no existe en la DB local, comprobamos si tiene claims de ADMIN
                    // Esto permite acceso de emergencia o inicial a administradores.
                    if (in_array('admin', $authUser->claims) || ($authUser->claims['rol'] ?? '') === 'admin') {
                        $this->logger->info('FirebaseAccessTokenHandler: Admin login detected for non-local user.', ['email' => $authUser->email]);
                        return new User($authUser->email, ['ROLE_ADMIN'], null);
                    }

                    $this->logger->warning('FirebaseAccessTokenHandler: User not found in database.', ['email' => $authUser->email]);
                    throw $e;
                }
            });

        } catch (\Exception $e) {
            // Loguear el error para depuración pero lanzar BadCredentialsException para el cliente
            $this->logger->error('FirebaseAccessTokenHandler: Token validation failed.', [
                'error' => $e->getMessage(),
                'token_prefix' => substr($accessToken, 0, 10) . '...'
            ]);

            throw new BadCredentialsException('Invalid or expired authentication token.');
        }
    }
}
