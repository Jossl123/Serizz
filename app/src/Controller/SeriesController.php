<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Genre;
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
    #[Route('/', name: 'app_series_index', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $NbG = 0;
        $page = $request->query->get('page', 1)-1;
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
                if (isset($_GET['syno'])) {
                    $search->andwhere('s.plot LIKE :syn')
                    ->setParameter('syn', '%'.$_GET['syno'].'%');
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
            'init' => $search,
            'genres' => $entityManager->getRepository(Genre::class)->findAll([])
        ]);
    }

    #[Route('/followed', name: 'app_series_show_followed', methods: ['GET'])]
    public function showFollowed(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $userSeries = $user->getSeries();
        $page = $request->query->get('page', 1)-1;
        $limit = 10;
        
        // We need the series to get only the followed ones
        $seriesCompleted = $entityManager->getRepository(Series::class)->findAllByCompletedSeries($user->getId());

        // Exclude the completed series from the followed ones
        // Would rather not do it in O(n) but it works
        foreach($seriesCompleted as $completed){
            $userSeries->removeElement($completed);
        }

        dump($userSeries);

        $seriesNb = $userSeries->count([]);
        if ($page > $seriesNb / $limit) {
            $page = ceil($seriesNb / $limit);
        }
        if ($page < 0) {
            $page = 0;
        }

        $series = $userSeries->slice($page * $limit, $limit);

        return $this->render('series/followed.html.twig', [
            'series' => $series,
            'pagesNb' => ceil($seriesNb / $limit),
            'page' => $page,
        ]);
    }

    #[Route('/completed', name: 'app_series_show_completed', methods: ['GET'])]
    public function showCompleted(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $page = $request->query->get('page', 1)-1;
        $limit = 10;
        
        $seriesCompleted = $entityManager->getRepository(Series::class)->findAllByCompletedSeries($user->getId());

        $seriesNb = count($seriesCompleted);
        if ($page > $seriesNb / $limit) {
            $page = ceil($seriesNb / $limit);
        }
        if ($page < 0) {
            $page = 0;
        }

        $seriesCompleted = array_slice((array)$seriesCompleted, $page * $limit, $limit);

        return $this->render('series/completed.html.twig', [
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

        $ratings = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
            ->where('r.series = :id')
            ->andWhere('r.checkrate = 1')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
        $rating = new Rating();
        $rating -> setUser($user);
        $rating -> setSeries($serie);
        $rating -> setCheckrate(0);
        $form = $this->createForm(SeriesRatingType::class, $rating);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $rating->setDate(new \DateTime());
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
                $percentages_seasons[$key] = 100;
            } else {
                $percentages_seasons[$key] = (int)($seen / $season_episode_nb * 100);
            }
        }
        if ($episode_nb == 0) {
            $percentage_serie = 100;
        } else {
            $percentage_serie = (int)($percentage_serie / $episode_nb * 100);
        }
        $ratings_displayed = array();
        for ($i=0; $i < 5; $i++) { 
            $lower = 2*$i+1;
            $upper = 2*$i+2;
            $query = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.series = :series_id')
                ->andWhere('r.value BETWEEN :lower AND :upper')
                ->setParameter('series_id', $id)
                ->setParameter('lower', $lower)
                ->setParameter('upper', $upper)
                ->getQuery();
                $ratings_displayed[$i] =$query->getSingleScalarResult();
        }
        if (isset($serie)) {
            return $this->render('series/show.html.twig', [
                'series' => $serie,
                'percentages_seasons' => $percentages_seasons,
                'percentage_serie' => $percentage_serie,
                'ratingForm' => $form->createView(),
                'ratings' => $ratings,
                'ratings_displayed' => $ratings_displayed
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
            $user->addSeries($episode->getSeason()->getSeries());
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
