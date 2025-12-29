<?php

use App\Kernel;
use App\Entity\Actividad;
use Doctrine\ORM\EntityManagerInterface;

require_once dirname(__FILE__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new class($kernel) extends \Symfony\Bundle\FrameworkBundle\Console\Application {
        public function doRun(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
            $kernel = $this->getKernel();
            $kernel->boot();
            $em = $kernel->getContainer()->get('doctrine')->getManager();
            
            $actividad = $em->getRepository(Actividad::class)->find(25);
            if ($actividad) {
                $actividad->setEstado('PENDIENTE');
                $em->flush();
                echo "OK";
            } else {
                echo "FAIL";
            }
            return 0;
        }
    };
};
