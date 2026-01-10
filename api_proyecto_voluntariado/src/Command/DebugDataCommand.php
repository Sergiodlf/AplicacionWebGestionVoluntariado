<?php

namespace App\Command;

use App\Repository\OrganizacionRepository;
use App\Repository\VoluntarioRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-data',
    description: 'Debugs entitfy counts and contents',
)]
class DebugDataCommand extends Command
{
    public function __construct(
        private VoluntarioRepository $voluntarioRepository,
        private OrganizacionRepository $organizacionRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $voluntarios = $this->voluntarioRepository->findAll();
        $io->title('Voluntarios (' . count($voluntarios) . ')');
        foreach ($voluntarios as $v) {
            $io->text(sprintf('- %s (Estado: %s)', $v->getDni(), $v->getEstadoVoluntario()));
        }

        $organizaciones = $this->organizacionRepository->findAll();
        $io->title('Organizaciones (' . count($organizaciones) . ')');
        foreach ($organizaciones as $o) {
            $io->text(sprintf('- %s (Estado: %s)', $o->getCif(), $o->getEstado()));
        }

        return Command::SUCCESS;
    }
}
