<?php

use App\Kernel;
use App\Entity\Inscripcion;
use Doctrine\ORM\EntityManagerInterface;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new class($kernel) {
        public function __construct(private Kernel $kernel)
        {
        }

        public function __invoke()
        {
            $this->kernel->boot();
            $container = $this->kernel->getContainer();
            /** @var EntityManagerInterface $em */
            $em = $container->get('doctrine')->getManager();

            echo "=== DEBUGGING INSCRIPCIONES ===\n";
            
            $inscripciones = $em->getRepository(Inscripcion::class)->findAll();
            
            echo "Total Inscriptions Found: " . count($inscripciones) . "\n\n";
            
            foreach ($inscripciones as $inscripcion) {
                $voluntario = $inscripcion->getVoluntario();
                $actividad = $inscripcion->getActividad();
                
                echo sprintf(
                    "[ID: %d] Voluntario: %s (%s) | Actividad: %s | ESTADO: '%s'\n",
                    $inscripcion->getId(),
                    $voluntario->getNombre(),
                    $voluntario->getDni(),
                    $actividad->getNombre(),
                    $inscripcion->getEstado()
                );
            }
            
            echo "\n=== END DEBUG ===\n";
        }
    };
};
