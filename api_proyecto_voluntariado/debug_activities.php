<?php

use App\Kernel;
use App\Entity\Actividad;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new class($kernel) {
        private $kernel;

        public function __construct($kernel)
        {
            $this->kernel = $kernel;
        }

        public function __invoke()
        {
            $this->kernel->boot();
            $container = $this->kernel->getContainer();
            $em = $container->get('doctrine')->getManager();

            $actividades = $em->getRepository(Actividad::class)->findAll();

            echo "Total Actividades: " . count($actividades) . "\n";
            foreach ($actividades as $a) {
                echo sprintf(
                    "ID: %d | Nombre: %s | EstadoAprobacion: '%s' | Estado: '%s' | Org: %s\n",
                    $a->getCodActividad(),
                    $a->getNombre(),
                    $a->getEstadoAprobacion(),
                    $a->getEstado(),
                    $a->getOrganizacion() ? $a->getOrganizacion()->getCif() : 'NULL'
                );
            }
        }
    };
};
