<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Series;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $seriesRepo = $entityManager
            ->getRepository(Series::class);

        $series = $seriesRepo->findBy(array(), null, 3, 0);
        return $this->render('default/index.html.twig', [
            "hall_of_fame" => $series,
        ]);
    }
}
