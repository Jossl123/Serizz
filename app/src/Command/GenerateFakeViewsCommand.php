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

        $flush_cd = 20;
        foreach($series->findAll() as $serie) {
            $output->writeln($serie->getId());

            foreach($users->findAll() as $user) {
                $this->genViewsSub($serie, $user);
            }

            $flush_cd--;
            if (!$flush_cd) {
                $flush_cd = 20;
                $this->em->flush();
            }
        }

        $this->em->flush();

    }

    public function genViewsSub(Series $serie, User $user) {
        $rand = rand()&127;

        if (rand <= 60) {
            return;
        }

        $seasons = $serie->getSeasons();
        if (!empty($seasons)) {
            $season = $rand > 125 ? $seasons->last() : $seasons->get(rand(0, $seasons->count()-1));
            if ($season) {
                $eps = $season->getEpisodes();
                $ep = $rand > 125 ? $eps->last() : $eps->get(rand(0, $eps->count()-1));
                if ($ep) {
                    $this->markAsSeen($user, $ep, $this->em, true);
                }
            }
        }
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