<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Series;
use Symfony\Component\Security\Http\Attribute\IsGranted;
class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        //TODO return the good series 
        $seriesRepo = $entityManager
            ->getRepository(Series::class);

        $series = $seriesRepo->findBy(array(), null, 4, 2);

        if (!$this->isGranted('ROLE_USER')) {
            return $this->render('default/showcase.html.twig', [
                "hall_of_fame" => $series
            ]);
        }

        return $this->render('default/index.html.twig', [
            "hall_of_fame" => $series,
            "recently_seen" => $series
        ]);
    }

}
