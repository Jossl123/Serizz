<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use App\Factory\UserFactory;

#[AsCommand(
    name: 'app:gen-users',
    description: 'Allows for the generation, in database, of fake users',
    hidden: false,
    aliases: ['app:fake-users']
)]
class GenerateFakeUsersCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nb = $input->getArgument('nb');
        $time = floor(microtime(true) * 1000);
        UserFactory::createMany($nb);
        $output->writeln("successfully created ".$nb." new users");
        $output->writeln("took ".round(floor(microtime(true) * 1000) - $time, 2)." ms");
        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('nb', InputArgument::REQUIRED, 'nb times')
        ;
    }
}
