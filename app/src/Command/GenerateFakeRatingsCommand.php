<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use App\Factory\RatingFactory;

#[AsCommand(
    name: 'app:gen-ratings',
    description: 'Allows for the generation, in database, of fake ratings',
    hidden: false,
    aliases: ['app:fake-ratings']
)]
class GenerateFakeRatingsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        RatingFactory::createMany($input->getArgument('nb'));
        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('nb', InputArgument::REQUIRED, 'nb times')
        ;
    }
}
