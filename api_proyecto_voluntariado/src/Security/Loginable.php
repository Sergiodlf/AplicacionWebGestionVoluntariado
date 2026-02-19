<?php

namespace App\Security;

/**
 * Interfaz para entidades que pueden hacer login.
 * Permite verificar de forma polimórfica si un usuario puede iniciar sesión.
 */
interface Loginable
{
    /**
     * Indica si el usuario puede iniciar sesión.
     */
    public function canLogin(): bool;

    /**
     * Devuelve el motivo por el que no puede iniciar sesión, o null si puede.
     */
    public function getLoginDeniedReason(): ?string;
}
