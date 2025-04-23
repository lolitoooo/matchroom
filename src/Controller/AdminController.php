<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Entity\User;
use App\Form\AdminHotelType;
use App\Form\AdminUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        // Récupérer le nombre d'utilisateurs
        $userCount = $entityManager->getRepository(User::class)->count([]);
        $hotelCount = $entityManager->getRepository(Hotel::class)->count([]);
        
        // Récupérer tous les utilisateurs et filtrer en PHP
        $allUsers = $entityManager->getRepository(User::class)->findAll();
        
        // Filtrer les utilisateurs avec le rôle USER
        $userClients = array_filter($allUsers, function($user) {
            return in_array('ROLE_USER', $user->getRoles()) && !in_array('ROLE_HOTEL', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles());
        });
        
        // Filtrer les utilisateurs avec le rôle HOTEL
        $hoteliers = array_filter($allUsers, function($user) {
            return in_array('ROLE_HOTEL', $user->getRoles());
        });
        
        // Récupérer tous les hôtels
        $hotels = $entityManager->getRepository(Hotel::class)->findAll();
        
        return $this->render('admin/dashboard.html.twig', [
            'user_count' => $userCount,
            'hotel_count' => $hotelCount,
            'user_clients' => $userClients,
            'hoteliers' => $hoteliers,
            'hotels' => $hotels,
        ]);
    }
    
    #[Route('/users', name: 'app_admin_users')]
    public function users(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }
    
    #[Route('/user/new', name: 'app_admin_user_new')]
    public function newUser(Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $user = new User();
        // Définir ROLE_USER comme rôle par défaut avant de créer le formulaire
        $user->setRoles(['ROLE_USER']);
        
        $form = $formFactory->create(AdminUserType::class, $user, ['is_new' => true]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer et hasher le mot de passe
            $password = $form->get('password')->getData();
            if ($password) {
                $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
            }
            
            // Définir le rôle en fonction de la sélection
            $userRole = $form->get('userRole')->getData();
            if ($userRole) {
                // Définir un seul rôle pour l'utilisateur
                $user->setRoles([$userRole]);
            } else {
                // Par défaut, attribuer le rôle ROLE_USER
                $user->setRoles(['ROLE_USER']);
            }
            
            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur créé avec succès !');
            return $this->redirectToRoute('app_admin_users');
        }
        
        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
    
    #[Route('/user/{id}/edit', name: 'app_admin_user_edit')]
    public function editUser(User $user, Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->create(AdminUserType::class, $user, ['is_new' => false]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour le mot de passe uniquement s'il est fourni
            $password = $form->get('password')->getData();
            if ($password) {
                $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
            }
            
            // Définir le rôle en fonction de la sélection
            $userRole = $form->get('userRole')->getData();
            if ($userRole) {
                // Définir un seul rôle pour l'utilisateur
                $user->setRoles([$userRole]);
            } else {
                // Conserver les rôles existants ou attribuer ROLE_USER par défaut
                $roles = $user->getRoles();
                if (empty($roles)) {
                    $user->setRoles(['ROLE_USER']);
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur modifié avec succès !');
            return $this->redirectToRoute('app_admin_users');
        }
        
        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
    
    #[Route('/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/user/{id}/ban', name: 'app_admin_user_ban', methods: ['POST'])]
    public function banUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('ban'.$user->getId(), $request->request->get('_token'))) {
            // Marquer l'utilisateur comme banni
            $user->setBanned(true);
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur banni avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/user/{id}/unban', name: 'app_admin_user_unban', methods: ['POST'])]
    public function unbanUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('unban'.$user->getId(), $request->request->get('_token'))) {
            // Marquer l'utilisateur comme non banni
            $user->setBanned(false);
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur débanni avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/hotels', name: 'app_admin_hotels')]
    public function hotels(EntityManagerInterface $entityManager): Response
    {
        $hotels = $entityManager->getRepository(Hotel::class)->findAll();
        
        return $this->render('admin/hotels.html.twig', [
            'hotels' => $hotels,
        ]);
    }
    
    #[Route('/hotel/new', name: 'app_admin_hotel_new')]
    public function newHotel(Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $hotel = new Hotel();
        $form = $formFactory->create(AdminHotelType::class, $hotel);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($hotel);
            $entityManager->flush();
            
            $this->addFlash('success', 'Hôtel créé avec succès !');
            return $this->redirectToRoute('app_admin_hotels');
        }
        
        return $this->render('admin/hotel_form.html.twig', [
            'form' => $form->createView(),
            'hotel' => $hotel,
        ]);
    }
    
    #[Route('/hotel/{id}/edit', name: 'app_admin_hotel_edit')]
    public function editHotel(Hotel $hotel, Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->create(AdminHotelType::class, $hotel);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Hôtel modifié avec succès !');
            return $this->redirectToRoute('app_admin_hotels');
        }
        
        return $this->render('admin/hotel_form.html.twig', [
            'form' => $form->createView(),
            'hotel' => $hotel,
        ]);
    }
    
    #[Route('/hotel/{id}/delete', name: 'app_admin_hotel_delete', methods: ['POST'])]
    public function deleteHotel(Hotel $hotel, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$hotel->getId(), $request->request->get('_token'))) {
            $entityManager->remove($hotel);
            $entityManager->flush();
            
            $this->addFlash('success', 'Hôtel supprimé avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_hotels');
    }
}
