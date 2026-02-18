<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Implementación concreta del servicio de correos electrónicos.
 */
class EmailService implements EmailServiceInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    public function sendEmail(string $to, string $subject, string $htmlContent): void
    {
        try {
            $email = (new Email())
                ->from('notificaciones4v@gmail.com')
                ->to($to)
                ->subject($subject)
                ->html($htmlContent);

            $this->mailer->send($email);
            $this->logger->info('EmailService: Email sent.', ['to' => $to]);
        } catch (\Throwable $e) {
            $this->logger->error('EmailService: Error sending email.', ['error' => $e->getMessage()]);
        }
    }
}
