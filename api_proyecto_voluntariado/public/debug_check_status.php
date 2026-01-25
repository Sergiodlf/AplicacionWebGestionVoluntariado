<?php
// public/debug_check_status.php
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use App\Kernel;

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new \Symfony\Component\HttpFoundation\Response(
        (function() use ($kernel) {
            $kernel->boot();
            $em = $kernel->getContainer()->get('doctrine')->getManager();
            $orgs = $em->getRepository(\App\Entity\Organizacion::class)->findAll();
            
            $output = "<pre>";
            foreach ($orgs as $org) {
                $st = $org->getEstado();
                $output .= "Nombre: " . $org->getNombre() . "\n";
                $output .= "Estado (raw): '" . $st . "'\n";
                $output .= "Length: " . strlen($st) . "\n";
                $output .= "Hex: " . bin2hex($st) . "\n";
                $output .= "----------------\n";
            }
            $output .= "</pre>";
            
            return $output;
        })()
    );
};
