<?php

namespace App\Service;

/**
 * Interfaz para el servicio de envío de correos electrónicos.
 */
interface EmailServiceInterface
{
    public function sendEmail(string $to, string $subject, string $htmlContent): void;
}
