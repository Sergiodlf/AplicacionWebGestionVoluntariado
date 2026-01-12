<?php

use App\Kernel;
use App\Entity\Organizacion;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload_runtime.php';

$autoload = require __DIR__ . '/vendor/autoload.php';
(new Dotenv())->bootEnv(__DIR__ . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine')->getManager();

$repo = $em->getRepository(Organizacion::class);
$user = $repo->find('A11111111'); // Assuming CIF. Wait, I don't know the CIF. I have the email.

$user = $repo->findOneBy(['email' => 'admin@balimentos.org']);

if (!$user) {
    echo "Organization not found\n";
    exit(1);
}

// Hash for 'admin123'
$hash = '$2y$13$YZYuSkfCPI/yOK2gGBrpLuw/6VOiumL33L8SUOUq9npYJKrGQdifa';
$user->setPassword($hash);

$em->flush();
echo "Updated password for " . $user->getEmail() . "\n";
