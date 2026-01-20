<?php

namespace App\Command;

use App\Repository\VoluntarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:delete-inscriptions',
    description: 'Deletes all inscriptions for a given Volunteer DNI.',
)]
class DeleteVolunteerInscriptionsCommand extends Command
{
    public function __construct(
        private VoluntarioRepository $voluntarioRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('dni', InputArgument::REQUIRED, 'The DNI of the volunteer')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dni = $input->getArgument('dni');

        $voluntario = $this->voluntarioRepository->findOneBy(['dni' => $dni]);

        if (!$voluntario) {
            $io->error(sprintf('Volunteer with DNI "%s" not found.', $dni));
            return Command::FAILURE;
        }

        $inscripciones = $voluntario->getInscripciones();
        $count = count($inscripciones);

        if ($count === 0) {
            $io->info(sprintf('Volunteer "%s" has no inscriptions.', $dni));
            return Command::SUCCESS;
        }

        $io->section(sprintf('Found %d inscriptions for volunteer "%s" (%s %s)', $count, $dni, $voluntario->getNombre(), $voluntario->getApellido1()));

        // Confirmación
        if (!$io->confirm('Are you sure you want to delete these inscriptions?', false)) {
            $io->note('Operation cancelled.');
            return Command::SUCCESS;
        }

        // iterate locally to avoid concurrent modification issues
        foreach ($inscripciones->toArray() as $inscripcion) {
            $voluntario->removeInscripcion($inscripcion);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Successfully deleted %d inscriptions.', $count));

        return Command::SUCCESS;
    }
}
