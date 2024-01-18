<?php

namespace App\Controller;

use App\Entity\Actor;
use App\Entity\Country;
use App\Entity\Episode;
use App\Entity\Genre;
use App\Entity\Series;
use App\Entity\Rating;
use App\Form\SeriesRatingType;
use App\Form\SeriesType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\OrderBy;
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
        if ($this->getUser() != null) {
            if ($this->getUser()->getBan() == 1) {
                return $this->redirectToRoute('app_banned');
            }
        }
        $NbG = 0;
        $page = $request->query->get('page', 1) - 1;
        $search = $entityManager->createQueryBuilder();
        $limit = 10;
        $seriesRepo = $entityManager
            ->getRepository(Series::class);

        $filters = $_GET;

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $series_match = $seriesRepo->findAllByFilters($_GET, $this->getUser());
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
        /** @var \App\Entity\User */
        $user = $this->getUser();
        
        $percentages = $entityManager->getRepository(Series::class)->findAllPercentages($user->getId());
        $resPercentages = [];
        foreach ($series as $se) {
            $seriesId = $se->getId();
            if ($percentages[$seriesId]["total_ep"] > 0){
                $resPercentages[$seriesId] = $percentages[$seriesId]["seen_ep"]/$percentages[$seriesId]["total_ep"];
            }
            else{
                $resPercentages[$seriesId] = 0;
            }
        }
        return $this->render('series/index.html.twig', [
            'series' => $series,
            'percentages' => $resPercentages,
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
        $page = $request->query->get('page', 1) - 1;
        $limit = 10;

        // We need the series to get only the followed ones
        $seriesCompleted = $entityManager->getRepository(Series::class)->findAllByCompletedSeries($user->getId());

        // Exclude the completed series from the followed ones
        // Would rather not do it in O(n) but it works
        foreach ($seriesCompleted as $completed) {
            $userSeries->removeElement($completed);
        }
        $seriesNb = $userSeries->count([]);
        if ($page > $seriesNb / $limit) {
            $page = ceil($seriesNb / $limit);
        }
        if ($page < 0) {
            $page = 0;
        }

        $series = $userSeries->slice($page * $limit, $limit);

        $percentages = $entityManager->getRepository(Series::class)->findAllPercentages($user->getId());
        $resPercentages = [];
        foreach ($series as $se) {
            $seriesId = $se->getId();
            if ($percentages[$seriesId]["total_ep"] > 0){
                $resPercentages[$seriesId] = $percentages[$seriesId]["seen_ep"]/$percentages[$seriesId]["total_ep"];
            }
            else{
                $resPercentages[$seriesId] = 0;
            }
        }
        return $this->render('series/followed.html.twig', [
            'series' => $series,
            'pagesNb' => ceil($seriesNb / $limit),
            'page' => $page,
            'percentages'=> $resPercentages
        ]);
    }

    #[Route('/completed', name: 'app_series_show_completed', methods: ['GET'])]
    public function showCompleted(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $page = $request->query->get('page', 1) - 1;
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

        $percentages = $entityManager->getRepository(Series::class)->findAllPercentages($user->getId());
        $resPercentages = [];
        foreach ($seriesCompleted as $se) {
            $seriesId = $se->getId();
            if ($percentages[$seriesId]["total_ep"] > 0){
                $resPercentages[$seriesId] = $percentages[$seriesId]["seen_ep"]/$percentages[$seriesId]["total_ep"];
            }
            else{
                $resPercentages[$seriesId] = 0;
            }
        }
        return $this->render('series/completed.html.twig', [
            'completed' => $seriesCompleted,
            'pagesNb' => ceil($seriesNb / $limit),
            'page' => $page,
            'percentages'=> $resPercentages
        ]);
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET', 'POST'])]
    public function show($id, EntityManagerInterface $entityManager, Request $request): Response
    {
        if ($this->getUser()->getBan() == 1) {
            return $this->redirectToRoute('app_banned');
        }

        if (!is_numeric($id)){

            return $this->render('bundles/TwigBundle/Exception/error404.html.twig');
        
        }
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $serie = $entityManager->getRepository(Series::class)->find($id);
        $percentages_seasons = array();
        $percentage_serie = 0;
        $episode_nb = 0;

        $ratings = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
            ->select('r')
            ->where('r.series = :id')
            ->andWhere('r.checkrate = 1')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
        if ($this->getUser() != null) {
            $ownRating = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
                ->join('r.user', 'u')
                ->where('r.series = :id')
                ->andWhere('u.id = :userId')
                ->setParameter('id', $id)
                ->setParameter('userId', $user->getId())
                ->getQuery()
                ->getResult();
            if (sizeof($ownRating) > 0) {
                $ownRating = $ownRating[0];
            } else {
                $ownRating = null;
            }
        } else {
            $ownRating = null;
        }
        $sortedRatings = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
            ->select('r')
            ->where('r.series = :id')
            ->andWhere('r.checkrate = 1');
        if (isset($_GET["by_rate"])) {
            $low = $_GET["by_rate"] * 2;
            $high = $low + 1;
            $sortedRatings = $sortedRatings->andWhere('r.value BETWEEN :low AND :high')
                ->setParameter('low', $low)
                ->setParameter('high', $high);
        }
        $sortedRatings = $sortedRatings
            ->orderBy('r.date', 'DESC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
        $rating = new Rating();
        $rating->setUser($user);
        $rating->setSeries($serie);
        $rating->setCheckrate(0);
        $form = $this->createForm(SeriesRatingType::class, $rating);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $rating->setDate(new \DateTime());
            $entityManager->persist($rating);
            $entityManager->flush();
            return $this->redirectToRoute('app_series_show', ['id' => $id]);
        }
        if (isset($serie)) {
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
        $ratings_displayed = array(0, 0, 0, 0, 0);
        if (sizeof($ratings) > 0) {
            for ($i = 0; $i < 5; $i++) {
                $lower = 2 * $i + 1;
                $upper = 2 * $i + 2;
                $query = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
                    ->select('COUNT(r.id)')
                    ->where('r.series = :series_id')
                    ->andWhere('r.value BETWEEN :lower AND :upper')
                    ->andWhere('r.checkrate = 1')
                    ->setParameter('series_id', $id)
                    ->setParameter('lower', $lower)
                    ->setParameter('upper', $upper)
                    ->getQuery();
                $ratings_displayed[$i] = $query->getSingleScalarResult() / sizeof($ratings);
            }
        }
        
            return $this->render('series/show.html.twig', [
                'series' => $serie,
                'percentages_seasons' => $percentages_seasons,
                'percentage_serie' => $percentage_serie,
                'ratingForm' => $form->createView(),
                'ratings' => $sortedRatings,
                'ratings_displayed' => $ratings_displayed,
                'ownRating' => $ownRating
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
        $see_all = boolval($request->query->get('all', 'false'));
        $all_prev = boolval($request->query->get('all_prev', 'true'));
        $episode = $entityManager->getRepository(Episode::class)->findOneBy(['id' => $to_update]);
        /** @var \App\Entity\User */
        $user = $this->getUser();
        if ($user->getEpisode()->contains($episode) && !$see_all) {
            $user->removeEpisode($episode);
            $entityManager->flush();
        } else {
            $user->addEpisode($episode);
            $user->addSeries($episode->getSeason()->getSeries());
            $entityManager->flush();
            $current_season = $episode->getSeason();
            if ($all_prev) {
                foreach ($current_season->getSeries()->getSeasons() as $season) {
                    foreach ($season->getEpisodes() as $ep) {
                        if ($ep == $episode) {
                            break;
                        }
                        if (!$user->getEpisode()->contains($ep)) {
                            $user->addEpisode($ep);
                            $entityManager->flush();
                        }
                    }
                    if ($current_season == $season) {
                        break;
                    }
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

    #[Route('/{id}', name: 'app_rating_delete')]
    public function deleteRatingUser(Rating $rating, EntityManagerInterface $entityManager, Request $request): Response
    {
        $serieId = $request->get('serieId');
        $entityManager->remove($rating);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_show', ['id' => $serieId]);
    }

    #[Route('/{id}/edit', name: 'app_series_edit', methods: ['GET', 'POST'])]
    #[IsGranted("ROLE_ADMIN")]
    public function edit($id, EntityManagerInterface $entityManager, Request $request): Response {

        $series = $entityManager
            ->getRepository(Series::class)
            ->find($id);

        $genres = $entityManager
            ->getRepository(Genre::class)
            ->findAll();

        $countries = $entityManager
            ->getRepository(Country::class)
            ->findAll();

        $seriesGenres = $series->getGenre();
        $seriesCountries = $series->getCountry();

        $changesArray = array();

        $changesArray[] = $_POST['title'] ?? "";
        $changesArray[] = $_POST['plot'] ?? "";
        $changesArray[] = $_POST['imdbID'] ?? "";
        $changesArray[] = $_POST['director'] ?? "";
        $changesArray[] = $_POST['youtube'] ?? "";
        $changesArray[] = $_POST['awards'] ?? "";
        $changesArray[] = $_POST['yearStart'] ?? "";
        $changesArray[] = $_POST['yearEnd'] ?? "";

        foreach($genres as $g) {
            if(isset($_POST[$g->getName()])) {
                $series->addGenre($g);
            } else {
                $series->removeGenre($g);
            }
        }

        foreach($changesArray as $change) {
            if($change != "") {
                $series->setTitle($changesArray[0]);
                $series->setPlot($changesArray[1]);
                $series->setImdb($changesArray[2]);
                $series->setDirector($changesArray[3]);
                $series->setYoutubeTrailer($changesArray[4]);
                $series->setAwards($changesArray[5]);
                $series->setYearStart(intval($changesArray[6]));
                $series->setYearEnd(intval($changesArray[7]));
            }
        }

        $entityManager->flush();

        return $this->render('series/_edit.html.twig', [
            'series' => $series,
            'seriesGenres' => $seriesGenres,
            'seriesCountries' => $seriesCountries,
            'genres' => $genres,
            'countries' => $countries
        ]);
    }
}
