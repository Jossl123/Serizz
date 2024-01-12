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
        // Getting the four most followed series
        $hallOfFameNb = 4;
        $qb = $entityManager->createQueryBuilder();
        $hallOfFameSeries = $qb->select('s')
            ->from('App:Series', 's')
            ->join('s.user', 'u')
            ->groupBy('s.id')
            ->orderBy('COUNT(u.id)', 'DESC')
            ->setMaxResults($hallOfFameNb)
        ->getQuery()->getResult();
        dump($qb->getQuery());

        $usersRepo = $entityManager
            ->getRepository(User::class);
        $ratingRepo = $entityManager
            ->getRepository(Rating::class);

        if (!$this->isGranted('ROLE_USER')) {
            return $this->render('default/showcase.html.twig', [
                "hall_of_fame" => $hallOfFameSeries,
                "user_nb" => $usersRepo->count([]),
                "watched_episodes" => $usersRepo->count([]) * 13, //TODO
            ]);
        }
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $followed_series = $user->getSeries();

        return $this->render('default/index.html.twig', [
            "hall_of_fame" => $hallOfFameSeries,
            "recently_seen" => $followed_series
        ]);
    }
}
