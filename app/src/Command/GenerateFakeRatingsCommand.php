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
        $moy = $input->getArgument('moy');
        $et = $input->getArgument('et');
        $nb = $input->getArgument('nb');

        RatingFactory::setMoyEt(is_null($moy) ? 2.5 : $moy, is_null($et) ? 0.75 : $et);
        $time = floor(microtime(true) * 1000);

        RatingFactory::createMany($nb);
        $output->writeln("successfully created " . $nb . " new ratings");
        $output->writeln("took " . round(floor(microtime(true) * 1000) - $time, 2) . " ms");
        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('nb', InputArgument::REQUIRED, 'nb times')
            ->addArgument('moy', InputArgument::OPTIONAL, 'gaussian\'s average')
            ->addArgument('et', InputArgument::OPTIONAL, 'gaussian\'s standard deviation')
        ;
    }
}
