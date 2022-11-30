<?php

namespace App\Command;

use App\Message\EncodeMessage;
use App\MessageHandler\EncodeMessageHandler;
use App\Service\InputPathScanner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cron',
    description: 'Add a short description for your command',
)]
class CronCommand extends Command
{
    public function __construct(
        private InputPathScanner $inputPathScanner
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // FIXME: COntinue here - create services this thing will call.
        $io = new SymfonyStyle($input, $output);
        $io->info('Scanning profiles....');

        $this->inputPathScanner->scanAll();

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
