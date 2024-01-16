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

#[AsCommand(
    name: 'app:gen-views',
    description: 'Allows for the generation, in database, of views.\nevery series goes through all users,
    so do try not to have more than a few thousands fake users when doing this',
    hidden: false,
    aliases: ['app:fake-views']
)]
class GenerateFakeViewsCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $time = floor(microtime(true) * 1000);
        $this->genViews($output);
        $output->writeln("did it");
        $output->writeln("took " . round(floor(microtime(true) * 1000) - $time, 2) . " ms");
        return Command::SUCCESS;
    }

    public function genViews(OutputInterface $output) {
        $series = $this->em->getRepository('App\Entity\Series');
        $users = $this->em->getRepository('App\Entity\User');

        foreach($series->findAll() as $serie) {
            foreach($users->findAll() as $user) {
                $rand = rand()&63;
                if ($rand > 60) {
                    /**$seasons = $serie->getSeasons();
                    $season = $seasons->
                    file_put_contents("brub.txt", "heyo");
                    $eps = $season->getEpisodes();
                    $ep = end($eps);
                    $this->markAsSeen($user, $ep, $this->em, true);*/
                } elseif ($rand > 5) {
                    $seasons = $serie->getSeasons();
                    if (!empty($seasons)) {
                        $season = $seasons->last();
                        if ($season) {
                            $eps = $season->getEpisodes();
                            $output->writeln(count($eps));
                            $ep = $eps->last();
                            $this->markAsSeen($user, $ep, $this->em, true);
                        }
                    }
                }
            }
        }

        $this->em->flush();

    }

    protected function markAsSeen(User $user, Episode $episode, EntityManagerInterface $entityManager, bool $see_all) {
        $user->addEpisode($episode);
        $user->addSeries($episode->getSeason()->getSeries());
        $current_season = $episode->getSeason();
        if ($see_all) {
            foreach ($current_season->getSeries()->getSeasons() as $season) {
                foreach ($season->getEpisodes() as $ep) {
                    if ($ep == $episode)break;
                    if (!$user->getEpisode()->contains($ep)) {
                        $user->addEpisode($ep);
                    }
                }
                if ($current_season == $season) break;
            }
        }
    }
}

// 2410678ms