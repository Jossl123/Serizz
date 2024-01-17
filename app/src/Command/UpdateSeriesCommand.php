<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Factory\UserFactory;
use App\Entity\Series;
use App\Entity\User;
use App\Entity\Season;
use App\Entity\Episode;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Controller\DefaultController;
use App\Factory\SeriesFactory;

#[AsCommand(
    name: 'app:update-series',
    description: 'updates a bunch of series all at once',
    hidden: false,
    aliases: ['app:series-update']
)]
class UpdateSeriesCommand extends Command
{
    private $em;
    private $client;
    private $defaultC;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $client)
    {
        $this->em = $entityManager;
        $this->client = $client;
        $this->defaultC = new DefaultController($client);

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $time = floor(microtime(true) * 1000);
        
        $mode = $input->getArgument('mode');

        if ($mode == "genre") {
            $genre = $input->getArgument('restriction');
            $series = $this->em;
        } elseif ($mode == "all") {
            $series = $this->em->getRepository('App\Entity\Series')->findAll();
        } else {
            $nb = $input->getArgument('mode');
            $nb = is_null($nb) ? 1 : $nb;
            $series = array();

            for ($i = 0; $i < $nb; $i++) {
                $ser = SeriesFactory::Random()->object();
                array_push($series, $ser);
            }
        }

        foreach($series as $serie) {
            $output->writeln("starting updating ".$serie->getTitle());
            $this->prepareRequestAndUpdate($series, $serie);
            $output->writeln("just updated ".$serie->getTitle());
        }

        $output->writeln("took " . round(floor(microtime(true) * 1000) - $time, 2) . " ms in total");
        return Command::SUCCESS;
    }

    protected function prepareRequestAndUpdate($series, Series $serie) {
        $url = "http://www.omdbapi.com/?apikey=3c7a370d&type=series&i=".$serie->getImdb();
        $response = $this->client->request('GET', $url);
        $content = $response->toArray();

        $this->defaultC->update($content, $series, $serie->getImdb(), $this->em);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('mode', InputArgument::OPTIONAL, 'the command\'s operation mode.')
            ->addArgument('restriction', InputArgument::OPTIONAL, 'restrictive value depending on the mode')
        ;
    }
}
