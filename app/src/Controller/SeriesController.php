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
    public function index(Request $request,EntityManagerInterface $entityManager): Response
    {
        $page = $request->query->get('page', 0);
        $search = $request->query->get('init', "");
        $limit=10;
        $seriesRepo = $entityManager
            ->getRepository(Series::class);
        
        if (isset($_GET['init'])){
            $series = $seriesRepo->findAll();
            $series_match = array();
            foreach($series as $serie){
                if (str_contains(strtoupper($serie->getTitle()),strtoupper($search))){
                    $series_match[] = $serie;
                }
            }
    
            $seriesNb = sizeof($series_match);

            if ($page > $seriesNb/$limit) {
                $page = (int)($seriesNb/$limit);
            }
            if ($page < 0) {
                $page = 0;
            }
    
            $series_match = array_slice($series_match, $page*$limit, $limit);
            $series = $series_match;
        } else {
            $seriesNb = $seriesRepo->count([]);
            if ($page > $seriesNb/$limit) {
                $page = (int)($seriesNb/$limit);
            }
            if ($page < 0) {
                $page = 0;
            }

            $series = $seriesRepo->findBy(array(), null, $limit, $page*$limit);
        }
        return $this->render('series/index.html.twig', [
            'series' => $series,
            'pagesNb' => (int)($seriesNb / $limit),
            'page' => $page,
            'init' => $search
        ]);
    }

    #[Route('/followed', name: 'app_series_show_followed', methods: ['GET'])]
    public function show_followed(): Response
    {   
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $series = $user->getSeries();

        return $this->render('series/followed.html.twig', [
            'series' => $series,
        ]);
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Series $series, EntityManagerInterface $entityManager): Response
    {
        return $this->render('series/show.html.twig', [
            'series' => $series,
        ]);
    }

    #[Route('/{id}/update', name: 'app_series_update', methods: ['GET'])]
    #[IsGranted("ROLE_USER")]
    public function episode_update(Request $request, EntityManagerInterface $entityManager, Series $series)
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
    public function show_poster(Series $series): Response
    {
        $response = new Response();
        $response->setContent(stream_get_contents($series->getPoster()));
        return $response;
    }


    #[Route('/{id}/update_follow', name: 'app_series_update_followed', methods: ['GET'])]
    public function series_update(Request $request, EntityManagerInterface $entityManager, Series $series): Response
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
        return $this->render('series/show.html.twig', [
            'series' => $series,
        ]);
    }

}
