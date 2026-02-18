<?php

namespace App\Security\Resolver;

/**
 * Interface para resolvers de usuario que desacoplan la búsqueda
 * de entidades de dominio del UnifiedUserProvider.
 */
interface UserResolverInterface
{
    /**
     * Busca el usuario en el dominio por su identificador.
     */
    public function resolve(string $identifier): ?object;

    /**
     * Retorna el rol que debe asignarse si este resolver tiene éxito.
     */
    public function getSupportedRole(): string;
}
