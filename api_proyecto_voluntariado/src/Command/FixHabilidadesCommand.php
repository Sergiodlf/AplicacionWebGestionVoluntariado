<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:fix-habilidades')]
class FixHabilidadesCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->em->getConnection();
        $sql = "SELECT CODACTIVIDAD, HABILIDADES FROM ACTIVIDADES";
        $rows = $conn->fetchAllAssociative($sql);

        foreach ($rows as $row) {
            $val = $row['HABILIDADES'];
            
            if ($val !== null) {
                // Check if valid JSON
                $decoded = json_decode($val);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Not valid JSON. Assume it's a raw string skill.
                    // Wrap it in array.
                    $output->writeln("Fixing ID {$row['CODACTIVIDAD']}: '$val' -> JSON Array");
                    
                    $newVal = json_encode([$val]); // "Cocina" -> ["Cocina"]
                    
                    $conn->executeStatement(
                        "UPDATE ACTIVIDADES SET HABILIDADES = :h WHERE CODACTIVIDAD = :id",
                        ['h' => $newVal, 'id' => $row['CODACTIVIDAD']]
                    );
                } else {
                     $output->writeln("ID {$row['CODACTIVIDAD']} is valid JSON.");
                }
            }
        }
        
        $output->writeln("Fix complete.");

        return Command::SUCCESS;
    }
}
