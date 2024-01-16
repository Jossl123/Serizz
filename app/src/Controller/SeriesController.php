<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Series;
use App\Entity\Rating;
use App\Form\SeriesRatingType;
use App\Form\SeriesType;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\Cast\Array_;
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
        $search = $request->query->get('init', "");
        $limit = 10;
        $seriesRepo = $entityManager
            ->getRepository(Series::class);

        if (isset($_GET['init'])) {
            $series = $seriesRepo->findAll();
            $series_match = array();
            foreach ($series as $serie) {
                if (str_contains(strtoupper($serie->getTitle()), strtoupper($search))) {
                    $series_match[] = $serie;
                }
            }

            $seriesNb = sizeof($series_match);

            if ($page > $seriesNb / $limit) {
                $page = ceil($seriesNb / $limit);
            }
            if ($page < 0) {
                $page = 0;
            }

            $series_match = array_slice($series_match, $page * $limit, $limit);
            $series = $series_match;
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
    public function showFollowed(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $userSeries = $user->getSeries();
        $page = $request->query->get('page', 0);
        $limit = 10;

        $seriesCompleted = array();
        // The performance is NOT optimal... around 1400 queries are done in dev
        foreach ($userSeries as $series) {
            $nbEpisodes = 0; // Total number of episodes of the series
            $seen = 0; // Number of episodes watched by the user of the series
            foreach ($series->getSeasons() as $key => $season) {
                $episodes = $season->getEpisodes();
                $nbEpisodesSeason = $episodes->count();
                $nbEpisodes += $nbEpisodesSeason;
                foreach ($episodes as $ep_id => $episode) {
                    // If the season contains the episode, increase seen by 1
                    $seen += $episode->getUser()->contains($user);
                }
            }
            if ($nbEpisodes == $seen) {
                array_push($seriesCompleted, $series);
            }
        }

        $seriesNb = $userSeries->count([]);
        if ($page > $seriesNb / $limit) {
            $page = ceil($seriesNb / $limit);
        }
        if ($page < 0) {
            $page = 0;
        }

        $series = $userSeries->slice($page * $limit, $limit);

        return $this->render('series/followed.html.twig', [
            'series' => $userSeries,
            'completed' => $seriesCompleted,
            'pagesNb' => ceil($seriesNb / $limit),
            'page' => $page,
        ]);
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET', 'POST'])]
    public function show(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $serie = $entityManager->getRepository(Series::class)->find($id);
        $percentages_seasons = array();
        $percentage_serie = 0;
        $episode_nb = 0;
        $ratings = $entityManager->getRepository(Rating::class)->findBy(array("series"=>$id));
        $rating = new Rating();
        $rating -> setUser($user);
        $rating -> setSeries($serie);
        $form = $this->createForm(SeriesRatingType::class, $rating);
        $form->handleRequest($request);
        dump($form->isSubmitted());
        dump($rating);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rating);
            $entityManager->flush();
            $this->addFlash('success', 'You successfully rated this serie !');
            return $this->redirectToRoute('app_series_show', ['id' => $id]);
        }
        foreach ($serie->getSeasons() as $key => $season) {
            $seen =  0;
            $season_episode_nb = $season->getEpisodes()->count();
            $episode_nb += $season_episode_nb;
            foreach ($season->getEpisodes() as $ep_id => $episode) {
                $seen += $episode->getUser()->contains($user);
            }
            $percentage_serie += $seen;
            if ($season_episode_nb == 0) {
                $percentages_seasons = 100;
            } else {
                $percentages_seasons[$key] = (int)($seen / $season_episode_nb * 100);
            }
        }
        if ($episode_nb == 0) $percentage_serie = 100;
        else $percentage_serie = (int)($percentage_serie/$episode_nb*100);
        if (isset($serie)) {
            return $this->render('series/show.html.twig', [
                'series' => $serie,
                'percentages_seasons' => $percentages_seasons,
                'percentage_serie' => $percentage_serie,
                'ratingForm' => $form->createView(),
                'ratings' => $ratings
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
        $see_all = $request->query->get('all', true);
        $episode = $entityManager->getRepository(Episode::class)->findOneBy(['id' => $to_update]);
        /** @var \App\Entity\User */
        $user = $this->getUser();
        if ($user->getEpisode()->contains($episode)) {
            $user->removeEpisode($episode);
            $entityManager->flush();
        } else {
            $user->addEpisode($episode);

            $entityManager->flush();
            $current_season = $episode->getSeason();
            if ($see_all) {
                foreach ($current_season->getSeries()->getSeasons() as $season) {
                    foreach ($season->getEpisodes() as $ep) {
                        if ($ep == $episode)break;
                        if (!$user->getEpisode()->contains($ep)) {
                            $user->addEpisode($ep);
                            $entityManager->flush();
                        }
                    }
                    if ($current_season == $season) break;
                }
            } 
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
