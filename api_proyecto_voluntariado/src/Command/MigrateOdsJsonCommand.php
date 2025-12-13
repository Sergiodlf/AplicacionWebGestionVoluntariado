<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:migrate-ods-json')]
class MigrateOdsJsonCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conn = $this->em->getConnection();
        $sql = "SELECT CODACTIVIDAD, ODS FROM ACTIVIDADES";
        $rows = $conn->fetchAllAssociative($sql);

        foreach ($rows as $row) {
            $val = $row['ODS'];
            $id = $row['CODACTIVIDAD'];

            if (empty($val)) {
                continue;
            }

            // check if already JSON
            json_decode($val);
            if (json_last_error() === JSON_ERROR_NONE) {
                $output->writeln("ID $id is already JSON. Skipping.");
                continue;
            }

            // Convert CSV to JSON
            if (str_contains($val, ',')) {
                 $parts = array_map('trim', explode(',', $val));
                 $newVal = json_encode($parts);
                 $output->writeln("ID $id (CSV): '$val' -> $newVal");
            } else {
                 // Single value not yet JSON
                 $newVal = json_encode([trim($val)]);
                 $output->writeln("ID $id (Single): '$val' -> $newVal");
            }

            $conn->executeStatement(
                "UPDATE ACTIVIDADES SET ODS = :ods WHERE CODACTIVIDAD = :id",
                ['ods' => $newVal, 'id' => $id]
            );
        }
        
        $output->writeln("ODS Migration complete.");

        return Command::SUCCESS;
    }
}
