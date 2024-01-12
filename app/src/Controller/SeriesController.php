<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Series;
use App\Form\SeriesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/series')]
class SeriesController extends AbstractController
{
    #[Route('/', name: 'app_series_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $page = $request->query->get('page', 0);
        $search = $entityManager->createQueryBuilder();
        $limit = 10;
        $seriesRepo = $entityManager
            ->getRepository(Series::class);
        $search->select('s')
        ->from('\App\Entity\Series','s');
        if (isset($_GET['init'])) {
            $search->andwhere('s.title LIKE :init')
                ->setParameter('init', $_GET['init']);

        } 
         if (isset($_GET['yearS'])) {
                $search->andwhere('s.yearStart = :ys')
                    ->setParameter('ys', $_GET['yearS']);
            
            $series_match = $search->getQuery()->getResult();
            $seriesNb = sizeof((array)$series_match);
            dump($_GET['yearS']);

            if ($page > $seriesNb / $limit) {
                $page = ceil($seriesNb / $limit);
            }
            if ($page < 0) {
                $page = 0;
            }

            $series_match = array_slice((array)$series_match, $page * $limit, $limit);
            $series = (array)$series_match;
        } else {
            $seriesNb = $seriesRepo->count([]);
            if ($page > $seriesNb / $limit) {
                $page = ceil($seriesNb / $limit);
            }
            if ($page < 0) {
                $page = 0;
            }

            $series = $seriesRepo->findBy(array(), null, $limit, $page * $limit);
        }
        return $this->render('series/index.html.twig', [
            'series' => $series,
            'pagesNb' => ceil($seriesNb / $limit),
            'page' => $page,
            'init' => $search
        ]);
    }

    #[Route('/followed', name: 'app_series_show_followed', methods: ['GET'])]
    public function showFollowed(Request $request): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $series = $user->getSeries();
        $page = $request->query->get('page', 0);
        $limit = 10;

        $seriesNb = $series->count([]);
        if ($page > $seriesNb / $limit) {
            $page = ceil($seriesNb / $limit);
        }
        if ($page < 0) {
            $page = 0;
        }

        $series = $series->slice($page * $limit, $limit);

        return $this->render('series/followed.html.twig', [
            'series' => $series,
            'pagesNb' => ceil($seriesNb / $limit),
            'page' => $page,
        ]);
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $serie = $entityManager->getRepository(Series::class)->find($id);
        $percentages_seasons = array();
        $percentage_serie = 0;
        $episode_nb = 0;
        foreach ($serie->getSeasons() as $key => $season) {
            $seen =  0;
            $season_episode_nb = $season->getEpisodes()->count();
            $episode_nb+=$season_episode_nb;
            foreach ($season->getEpisodes() as $ep_id => $episode){
                $seen+=$episode->getUser()->contains($user);
            }
            $percentage_serie+=$seen;
            if ($season_episode_nb == 0)$percentages_seasons=100;
            else $percentages_seasons[$key] = (int)($seen/$season_episode_nb*100);
        }
        if ($episode_nb == 0) $percentage_serie = 100;
        else $percentage_serie = (int)($percentage_serie/$episode_nb*100);
        dump($percentages_seasons);
        if (isset($serie)) {
            return $this->render('series/show.html.twig', [
                'series' => $serie,
                'percentages_seasons' => $percentages_seasons,
                'percentage_serie' => $percentage_serie,
            ]);
        } else {
            return $this->render('bundles/TwigBundle/Exception/error404.html.twig');
        }
    }

    #[Route('/{id}/update', name: 'app_series_update', methods: ['GET'])]
    #[IsGranted("ROLE_USER")]
    public function episodeUpdate(Request $request, EntityManagerInterface $entityManager, Series $series)
    {
        $to_update = $request->query->get('update', 0);
        $episode = $entityManager->getRepository(Episode::class)->findOneBy(['id' => $to_update]);

        /** @var \App\Entity\User */
        $user = $this->getUser();

        if ($user->getEpisode()->contains($episode)) {
            $user->removeEpisode($episode);
            $entityManager->flush();
        } else {
            $user->addEpisode($episode);
            $entityManager->flush();
        }

        return new JsonResponse(array('success' => "true"));
    }


    #[Route('/poster/{id}', name: 'app_series_poster', methods: ['GET'])]
    public function showPoster(Series $series): Response
    {
        $response = new Response();
        $response->setContent(stream_get_contents($series->getPoster()));
        return $response;
    }


    #[Route('/{id}/update_follow', name: 'app_series_update_followed', methods: ['GET'])]
    public function seriesUpdate(Request $request, EntityManagerInterface $entityManager, Series $series): Response
    {
        $to_update = $request->query->get('update', 0);
        $series = $entityManager->getRepository(Series::class)->findOneBy(['id' => $to_update]);

        /** @var \App\Entity\User */
        $user = $this->getUser();

        if ($user->getSeries()->contains($series)) {
            $user->removeSeries($series);
            $entityManager->flush();
        } else {
            $user->addSeries($series);
            $entityManager->flush();
        }
        return new JsonResponse(array('success' => "true"));
    }
}
