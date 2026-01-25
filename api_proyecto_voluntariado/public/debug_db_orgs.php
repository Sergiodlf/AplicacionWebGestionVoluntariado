<?php
// public/debug_db_orgs.php
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use App\Kernel;

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new \Symfony\Component\HttpFoundation\Response(
        (function() use ($kernel) {
            $kernel->boot();
            $em = $kernel->getContainer()->get('doctrine')->getManager();
            $orgs = $em->getRepository(\App\Entity\Organizacion::class)->findAll();
            
            $output = "<h1>Organizaciones en DB (" . count($orgs) . ")</h1>";
            $output .= "<table border='1'><tr><th>CIF</th><th>Nombre</th><th>Email</th><th>Estado</th><th>Sector</th></tr>";
            
            foreach ($orgs as $org) {
                $output .= "<tr>";
                $output .= "<td>" . $org->getCif() . "</td>";
                $output .= "<td>" . $org->getNombre() . "</td>";
                $output .= "<td>" . $org->getEmail() . "</td>";
                $output .= "<td>" . ($org->getEstado() ?: 'NULL') . "</td>";
                $output .= "<td>" . $org->getSector() . "</td>";
                $output .= "</tr>";
            }
            $output .= "</table>";
            
            return $output;
        })()
    );
};
