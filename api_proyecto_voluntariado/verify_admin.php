
require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use App\Entity\Voluntario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
/** @var EntityManagerInterface $em */
$em = $container->get('doctrine')->getManager();

echo "=== VERIFYING ADMIN USER ===\n";
echo "Database URL: " . $_SERVER['DATABASE_URL'] . "\n";

$email = 'admin@admin.com';
$user = $em->getRepository(Voluntario::class)->findOneBy(['correo' => $email]);

if ($user) {
    echo "FOUND: User exists!\n";
    echo "DNI: " . $user->getDni() . "\n";
    echo "Name: " . $user->getNombre() . "\n";
} else {
    echo "NOT FOUND: User '$email' does not exist in this database.\n";
    
    // List all users to see what's there
    echo "Listing first 5 users found:\n";
    $all = $em->getRepository(Voluntario::class)->findBy([], null, 5);
    foreach ($all as $u) {
        echo "- " . $u->getCorreo() . " (" . $u->getDni() . ")\n";
    }
}
