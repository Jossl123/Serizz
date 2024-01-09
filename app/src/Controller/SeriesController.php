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

#[Route('/series')]
class SeriesController extends AbstractController
{
    #[Route('/', name: 'app_series_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $page = $request->query->get('page', 0);
        $limit = 10;
        $seriesRepo = $entityManager
            ->getRepository(Series::class);

        $series = $seriesRepo->findBy(array(), null, $limit, $page * $limit);
        $seriesNb = $seriesRepo->count([]);
        if ($page > $seriesNb/$limit) {
            $page = (int)($seriesNb/$limit);
        }
        if ($page < 0) {
            $page = 0;
        }
        $series = $seriesRepo->findBy(array(), null, $limit, $page*$limit);
        return $this->render('series/index.html.twig', [
            'series' => $series,
            'pagesNb' => $seriesNb / $limit,
            'page' => $page
        ]);
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Series $series): Response
    {
        return $this->render('series/show.html.twig', [
            'series' => $series,
        ]);
    }

    #[Route('/{id}?update={update}', name: 'app_series_update', methods: ['GET'])]
    #[isGranted("ROLE_USER")]
    public function episode_update(EntityManagerInterface $entityManager, Series $series): Response
    {
        $episode = $entityManager->getRepository(Episode::class)->find($_GET['update']);

        var_dump($episode);

        /** @var \App\Entity\User */
        $user = $this->getUser();

        if (in_array($user->getEpisode(), $episode)) {
            $user->removeEpisode($episode);
            $entityManager->flush();
        } else {
            $user->addEpisode($episode);
            $entityManager->flush();
        }

        return $this->render('series/show.html.twig', [
            'series' => $series,
        ]);
    }

    #[Route('/poster/{id}', name: 'app_series_poster', methods: ['GET'])]
    public function show_poster(Series $series): Response
    {
        $response = new Response();
        $response->setContent(stream_get_contents($series->getPoster()));
        return $response;
    }

    
}
