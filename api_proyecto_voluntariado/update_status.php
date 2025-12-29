<?php
use App\Kernel;
use App\Entity\Actividad;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new class($kernel) extends \Symfony\Bundle\FrameworkBundle\Console\Application {
        public function doRun(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
            $kernel = $this->getKernel();
            $kernel->boot();
            $em = $kernel->getContainer()->get('doctrine')->getManager();
            
            // Find activity 25 and set to PENDIENTE
            $actividad = $em->getRepository(Actividad::class)->find(25);
            if ($actividad) {
                $actividad->setEstado('PENDIENTE');
                $em->flush();
                echo "Actividad 25 actualizada a PENDIENTE\n";
            } else {
                echo "Actividad 25 no encontrada\n";
            }
            return 0;
        }
    };
};
