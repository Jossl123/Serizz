<?php

namespace App\Controller;

use App\Entity\Country;
use App\Entity\Rating;
use App\Entity\Series;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
//#[IsGranted('ROLE_ADMIN')]

class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        dump($this->getUser());
        $page = $request->query->get('page', 1) - 1;
        $search = $request->query->get('search', "");
        $searchForUser = $request->query->get('user', false);
        $searchForAdmin = $request->query->get('admin', false);
        $searchForSuperAdmin = $request->query->get('superAdmin', false);

        $searchArray = array($searchForUser, $searchForAdmin, $searchForSuperAdmin);

        $limit = 10;
        $usersRepo = $entityManager
            ->getRepository(User::class);

        if (isset($_GET['search'])) {
            $users = $usersRepo->findAll();
            $users_match = array();

            if ($searchForUser || $searchForAdmin || $searchForSuperAdmin) {
                foreach ($users as $user) {
                    $userRole = $user->getRoles();
                    foreach ($searchArray as $search) {
                        for ($i = 0; $i < sizeof($userRole); $i++) {
                            if ($search == $userRole[$i]) {
                                $users_match[] = $user;
                            }
                        }
                    }
                }

                $userNb = sizeof($users_match);
            } else {
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
            }

            $users_match = array_slice($users_match, $page * $limit, $limit);
            $users = $users_match;
        } else {
            $userNb = $usersRepo->count([]);

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

    #[IsGranted('ROLE_USER')]
    #[Route('/followed', name: 'app_user_index_followed', methods: ['GET'])]
    public function index_followed(Request $request, EntityManagerInterface $entityManager): Response
    {
        $page = $request->query->get('page', 1) - 1;
        $search = $request->query->get('search', "");
        $searchForUser = $request->query->get('user', false);
        $searchForAdmin = $request->query->get('admin', false);
        $searchForSuperAdmin = $request->query->get('superAdmin', false);

        $searchArray = array($searchForUser, $searchForAdmin, $searchForSuperAdmin);

        $limit = 10;

        // Only get the users followed by the logged in user
        // TODO
        $usersRepo = $entityManager
            ->getRepository(User::class);

        if (isset($_GET['search'])) {
            $users = $usersRepo->findAll();
            $users_match = array();

            if ($searchForUser || $searchForAdmin || $searchForSuperAdmin) {
                foreach ($users as $user) {
                    $userRole = $user->getRoles();
                    foreach ($searchArray as $search) {
                        for ($i = 0; $i < sizeof($userRole); $i++) {
                            if ($search == $userRole[$i]) {
                                $users_match[] = $user;
                            }
                        }
                    }
                }

                $userNb = sizeof($users_match);
            } else {
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
            }

            $users_match = array_slice($users_match, $page * $limit, $limit);
            $users = $users_match;
        } else {
            $userNb = $usersRepo->count([]);

            if ($page > $userNb / $limit) {
                $page = ceil($userNb / $limit);
            }
            if ($page < 0) {
                $page = 0;
            }

            $users = $usersRepo->findAllByFollowed($this->getUser());
            //$users = $usersRepo->findBy(array(), ['registerDate' => 'DESC'], $limit, $page * $limit);
        }

        return $this->render('user/followed.html.twig', [
            'users' => $users,
            'pagesNb' => ceil($userNb / $limit),
            'page' => $page,
            'search' => $search
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/new', name: 'app_user_update_followed', methods: ['GET', 'POST'])]
    public function update_followed(Request $request, EntityManagerInterface $entityManager): Response
    {
        $followedId = $request->query->get('id', 0);
        $userToFollow = $entityManager->getRepository(User::class)->findOneBy(array('id' => $followedId));
        $this->getUser()->addFollowed($userToFollow);
        $entityManager->flush();
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
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
    public function show(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        $seriesRepo = $user->getSeries();
        $page = $request->query->get('page', 1) - 1;
        $limit = 10;
        $seriesNb = $seriesRepo->count([]);

        if ($page > $seriesNb / $limit) {
            $page = ceil($seriesNb / $limit);
        }

        if ($page < 0) {
            $page = 0;
        }

        // Cast is needed since page*limit is a float 
        $series = $seriesRepo->slice((int)$page * $limit, $limit);

        $ratings = $entityManager
            ->getRepository(Rating::class)
            ->findBy(array('user' => $user->getId()), array('date' => 'DESC'));

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'followedSeries' => $series,
            'ratings' => $ratings,
            'pagesNb' => ceil($seriesNb / $limit),
            'page' => $page
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_user_embody', methods: ['GET'])]
    public function embody(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            $entityManager->persist($user);
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

    #[Route('/{id}/settings', name: 'app_user_settings', methods: ['GET', 'POST'])]
    public function settings(User $user, EntityManagerInterface $entityManager, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $countries = $entityManager
            ->getRepository(Country::class)
            ->findAll();

        $name = $request->request->get('name');
        $country = $request->request->get('country');
        $password = $request->request->get('password');
        $passwordConfirm = $request->request->get('passwordConfirm');
        $currentPassword = $request->request->get('currentPassword');

        if ($currentPassword != null) {
            $isPasswordValid = $passwordHasher->isPasswordValid($user, $currentPassword);
        } else {
            $isPasswordValid = false;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($currentPassword != null && $isPasswordValid) {
                foreach ($countries as $c) {
                    if ($c->getName() == $country) {
                        $user->setCountry($c);
                        $entityManager->flush();
                    }
                }

                if ($name != null && $name != $user->getName()) {
                    $user->setName($name);
                    $entityManager->flush();
                }
                if ($password != null && $passwordConfirm != null) {
                    if ($password == $passwordConfirm) {
                        $user->setPassword($password);
                        $entityManager->flush();
                    }
                }
            } else {
                $this->addFlash('err', 'Incorrect password');
            }
        }

        return $this->render('user/_userEdit.html.twig', [
            'user' => $user,
            'countries' => $countries
        ]);
    }
}
