<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
//#[IsGranted('ROLE_ADMIN')]

class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $page = $request->query->get('page', 0);
        $search = $request->query->get('search', "");
        $limit = 10;
        $usersRepo = $entityManager
            ->getRepository(User::class);

        if (isset($_GET['search'])) {
            $users = $usersRepo->findAll();
            $users_match = array();
            foreach ($users as $user) {
                if (str_contains(strtoupper($user->getEmail()), strtoupper($search))) {
                    $users_match[] = $user;
                }
            }

            $userNb = sizeof($users_match);

            if ($page > $userNb / $limit) {
                $page = ceil($userNb / $limit);
            }
            if ($page < 0) {
                $page = 0;
            }

            $users_match = array_slice($users_match, $page * $limit, $limit);
            $users = $users_match;
        } else {
            $userNb = $usersRepo->count([]);
            dump($userNb);
            if ($page > $userNb / $limit) {
                $page = ceil($userNb / $limit);
            }
            if ($page < 0) {
                $page = 0;
            }

            $users = $usersRepo->findBy(array(), ['registerDate' => 'DESC'], $limit, $page * $limit);
        }


        return $this->render('user/index.html.twig', [
            'users' => $users,
            'pagesNb' => ceil($userNb / $limit),
            'page' => $page,
            'search' => $search
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_user_embody', methods: ['GET'])]
    public function embody(User $user): Response
    {
        //TODO
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
