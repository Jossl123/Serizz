<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use App\Factory\UserFactory;

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
        UserFactory::createMany($input->getArgument('nb'));
        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('nb', InputArgument::REQUIRED, 'nb times')
        ;
    }
}
