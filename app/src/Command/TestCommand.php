<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:a-comm',
    description: 'a fookin test command',
    hidden: false,
    aliases: ['app:testcomm']
)]
class TestCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln('testy stuff');

        return Command::SUCCESS;
    }
}
