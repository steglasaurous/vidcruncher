<?php

namespace App\Command;

use App\Service\AssemblyScanner;
use App\Service\InputPathScanner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cron',
    description: 'Add a short description for your command',
)]
class CronCommand extends Command
{
    public function __construct(
        private InputPathScanner $inputPathScanner,
        private AssemblyScanner $assemblyScanner
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Scanning profiles....');

        $this->inputPathScanner->scanAll();

        $io->info('Checking for assemblies....');
        $this->assemblyScanner->assembleReadyProjects();

        return Command::SUCCESS;
    }
}
