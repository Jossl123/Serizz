<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Series;
use App\Entity\Rating;
use App\Form\SeriesRatingType;
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
        $NbG = 0;
        $page = $request->query->get('page', 0);
        $search = $entityManager->createQueryBuilder();
        $limit = 10;
        $seriesRepo = $entityManager
            ->getRepository(Series::class);

        if ($_SERVER['REQUEST_METHOD'] == 'GET'){
                $search->select('s')
                ->from('\App\Entity\Series','s');

                if (isset($_GET['init'])) {
                    $search->andwhere('s.title LIKE :init')
                    ->setParameter('init', '%'.$_GET['init'].'%');
                }

                if (isset($_GET['synop'])) {
                    $search->andwhere('s.plot LIKE :syn')
                    ->setParameter('syn', '%'.$_GET['synop'].'%');
                }

                if (isset($_GET['Syear']) and !isset($_GET['yearE'])) {
                    $search->andwhere('s.yearStart >= :ys')
                    ->setParameter('ys', $_GET['Syear'])
                    ->orderBy('s.yearStart', 'ASC');
                }

                if (isset($_GET['yearE']) and !isset($_GET['Syear'])) {
                    $search->andwhere('s.yearEnd = :ye')
                    ->setParameter('ye', $_GET['yearE']);
                }

                if (isset($_GET['yearE']) and isset($_GET['Syear'])) {
                    $search->andwhere('s.yearStart >= :ys')
                    ->andwhere('s.yearStart <= :ye')
                    ->setParameter('ys', $_GET['Syear'])
                    ->setParameter('ye', $_GET['yearE']);
                }
                dump($_GET['genres']);
                if (isset($_GET['genres'])){
                    $tousGenres = explode("_", $_GET['genres']);
                    $subsearch = $entityManager->createQueryBuilder();
                    $subsearch->select('sub_s')
                    ->from('\App\Entity\Series','sub_s')
                    ->join('sub_s.genre','g')
                    ->andWhere('g.name IN (:genres)');
                    $search->setParameter('genres', $tousGenres);
                    $search->andWhere($search->expr()->in('s.id', $subsearch->getDQL()));

                }

                if (isset($_GET['grade'])) {
                    $search->join()
                    ->andwhere('s.yearStart >= :ys')
                    ->setParameter('ys', $_GET['Syear'])
                    ->orderBy('s.yearStart', 'ASC');
                }
                $series_match = $search->getQuery()->getResult();
                $seriesNb = sizeof((array)$series_match);
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

        $rating = new Rating();
        $rating -> setUser($user);
        $rating -> setSeries($serie);
        //$rating -> setValue($request -> query -> get("value", 5));
        //$rating -> setComment($request -> query -> get("comment", "No comment added"));
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
