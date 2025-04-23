<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Hotel;
use App\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(HotelRepository $hotelRepository): Response
    {
        // Vérifier si user connecté
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Si user est hotelier, récupérer son hôtel
        $hotel = null;
        if (in_array('ROLE_HOTEL', $user->getRoles())) {
            $hotel = $hotelRepository->findOneBy(['user' => $user]);
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'hotel' => $hotel,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $hotel = null;
        if (in_array('ROLE_HOTEL', $user->getRoles())) {
            $hotel = $entityManager->getRepository(Hotel::class)->findOneBy(['user' => $user]);
        }

        if ($request->isMethod('POST')) {
            $user->setFirstname($request->request->get('firstname'));
            $user->setName($request->request->get('name'));
            $user->setPhone($request->request->get('phone'));

            if ($hotel) {
                $hotel->setName($request->request->get('hotel_name'));
                $hotel->setAddress($request->request->get('hotel_address'));
                $hotel->setDescription($request->request->get('hotel_description'));
                
                if ($request->request->has('hotel_latitude') && $request->request->has('hotel_longitude')) {
                    $hotel->setLatitude($request->request->get('hotel_latitude'));
                    $hotel->setLongitude($request->request->get('hotel_longitude'));
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'hotel' => $hotel,
        ]);
    }
    
    #[Route('/profile/change-password', name: 'app_profile_change_password')]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');
            
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('app_profile');
            }
            
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_profile');
            }
            
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre mot de passe a été mis à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }
        
        return $this->redirectToRoute('app_profile');
    }
}
