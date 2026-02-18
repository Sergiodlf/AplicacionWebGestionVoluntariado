<?php

namespace App\Security;

use App\Security\Resolver\UserResolverInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * Proveedor de usuarios unificado que delega la resolución a resolvers específicos de dominio.
 */
class UnifiedUserProvider implements UserProviderInterface
{
    /**
     * @param iterable<UserResolverInterface> $resolvers
     */
    public function __construct(
        #[TaggedIterator('app.user_resolver')]
        private iterable $resolvers
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        foreach ($this->resolvers as $resolver) {
            $domainUser = $resolver->resolve($identifier);
            if ($domainUser) {
                return new \App\Security\User\SecurityUser(
                    $identifier, 
                    [$resolver->getSupportedRole()], 
                    $domainUser
                );
            }
        }

        throw new UserNotFoundException(sprintf('User with email "%s" not found.', $identifier));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === \App\Security\User\SecurityUser::class;
    }
}
