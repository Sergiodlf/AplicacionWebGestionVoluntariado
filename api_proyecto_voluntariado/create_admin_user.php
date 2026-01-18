
require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use App\Entity\Voluntario;
use App\Entity\Ciclo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
/** @var EntityManagerInterface $em */
$em = $container->get('doctrine')->getManager();
/** @var UserPasswordHasherInterface $hasher */
$hasher = $container->get('security.user_password_hasher');

echo "=== CREATING ADMIN USER ===\n";

$email = 'admin@admin.com';

// Check if exists
$existing = $em->getRepository(Voluntario::class)->findOneBy(['correo' => $email]);
if ($existing) {
    echo "User $email already exists!\n";
    exit(0);
}

$admin = new Voluntario();
$admin->setDni('ADMIN0001'); // Dummy DNI
$admin->setNombre('Administrador');
$admin->setApellido1('Sistema');
$admin->setApellido2('Principal');
$admin->setCorreo($email);
$admin->setZona('Central');
$admin->setFechaNacimiento(new \DateTime('1990-01-01'));
$admin->setCoche(true);
$admin->setExperiencia('Administrator account');

// Password
$hashedPassword = $hasher->hashPassword($admin, '1234567');
$admin->setPassword($hashedPassword);

// Try to find a Ciclo
$ciclo = $em->getRepository(Ciclo::class)->findOneBy([]);
if ($ciclo) {
    $admin->setCiclo($ciclo);
} else {
    // If no ciclo exists, we might have an issue if mappedBy/nullable=false but entity says nullable=true.
    // Assuming nullable=true based on code reading.
    echo "Warning: No Ciclo found. Proceeding without Ciclo.\n";
}

try {
    $em->persist($admin);
    $em->flush();
    echo "SUCCESS: Admin user created with DNI 'ADMIN0001'.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
