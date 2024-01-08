<?php

namespace App\Controller;

use App\Entity\Series;
use App\Form\SeriesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/series')]
class SeriesController extends AbstractController
{
    #[Route('/', name: 'app_series_index', methods: ['GET'])]
    public function index(Request $request,EntityManagerInterface $entityManager): Response
    {
        $page = $request->query->get('page', 0);
        $limit=10;
        $series = $entityManager
            ->getRepository(Series::class)
            ->findBy(array(), null, $limit, $page*$limit);

        return $this->render('series/index.html.twig', [
            'series' => $series,
        ]);
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Series $series): Response
    {
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
