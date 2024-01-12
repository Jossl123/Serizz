<?php

namespace App\Controller;

use App\Entity\Rating;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Series;
use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        //TODO return the good series
        $seriesRepo = $entityManager
            ->getRepository(Series::class);
        $usersRepo = $entityManager
            ->getRepository(User::class);
        $ratingRepo = $entityManager
            ->getRepository(Rating::class);
        $series = $seriesRepo->findBy(array(), null, 4, 2);

        if (!$this->isGranted('ROLE_USER')) {
            return $this->render('default/showcase.html.twig', [
                "hall_of_fame" => $series,
                "user_nb" => $usersRepo->count([]),
                "watched_episodes" => $usersRepo->count([]) * 13,//TODO
            ]);
        }
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $followed_series = $user->getSeries();

        return $this->render('default/index.html.twig', [
            "hall_of_fame" => $series,
            "recently_seen" => $followed_series
        ]);
    }
}
