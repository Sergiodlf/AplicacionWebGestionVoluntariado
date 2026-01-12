<?php

use App\Kernel;
use App\Entity\Voluntario;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

require_once __DIR__ . '/vendor/autoload_runtime.php';

$autoload = require __DIR__ . '/vendor/autoload.php';
(new Dotenv())->bootEnv(__DIR__ . '/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine.orm.entity_manager');
$hasher = $container->get('security.user_password_hasher');

$repo = $em->getRepository(Voluntario::class);
$user = $repo->find('11111111A');

if (!$user) {
    echo "ERROR: User 11111111A not found.\n";
    exit(1);
}

echo "EMAIL: " . $user->getCorreo() . "\n";

// Reset password to 'start123'
$hashed = $hasher->hashPassword($user, 'start123');
$user->setPassword($hashed);
$em->flush();

echo "PASSWORD_RESET: OK\n";
