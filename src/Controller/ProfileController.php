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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(EntityManagerInterface $entityManager, HotelRepository $hotelRepository): Response
    {
        // Vérifier si user connecté
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Si user est hotelier, récupérer son hôtel
        $hotel = null;
        $roomTypes = [];
        
        if (in_array('ROLE_HOTEL', $user->getRoles())) {
            $hotel = $hotelRepository->findOneBy(['user' => $user]);
            
            if ($hotel) {
                // Récupérer directement les types de chambres depuis la base de données
                $roomTypeRepository = $entityManager->getRepository(\App\Entity\RoomType::class);
                $roomTypes = $roomTypeRepository->findBy(['hotel' => $hotel]);
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'hotel' => $hotel,
            'hotel_profiles_directory' => $this->getParameter('hotel_profiles_directory'),
            'roomTypes' => $roomTypes,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $hotel = null;
        if (in_array('ROLE_HOTEL', $user->getRoles())) {
            $hotel = $entityManager->getRepository(Hotel::class)->findOneBy(['user' => $user]);
            
            // Vérifier si l'image de profil existe physiquement
            if ($hotel && $hotel->getProfileImageUrl()) {
                $imagePath = $this->getParameter('hotel_profiles_directory').'/'.$hotel->getProfileImageUrl();
                if (!file_exists($imagePath)) {
                    // L'image n'existe pas physiquement, on met à null la valeur en base
                    $hotel->setProfileImageUrl(null);
                    $entityManager->flush();
                    $this->addFlash('warning', 'L\'image de profil précédente n\'a pas été trouvée et a été réinitialisée.');
                }
            }
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
                
                // Gestion de l'image de profil
                $profileImageFile = $request->files->get('hotel_profile_image');
                if ($profileImageFile) {
                    // Vérification de la taille du fichier (max 2 Mo)
                    $maxFileSize = 2 * 1024 * 1024; // 2 Mo en octets
                    if ($profileImageFile->getSize() > $maxFileSize) {
                        $this->addFlash('error', 'L\'image est trop volumineuse. La taille maximale autorisée est de 2 Mo.');
                    } else {
                        // Vérification du type MIME
                        $allowedMimeTypes = ['image/jpeg', 'image/png'];
                        if (!in_array($profileImageFile->getMimeType(), $allowedMimeTypes)) {
                            $this->addFlash('error', 'Le format de l\'image n\'est pas valide. Utilisez uniquement des images JPG ou PNG.');
                        } else {
                            $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeFilename = $slugger->slug($originalFilename);
                            $newFilename = $safeFilename.'-'.uniqid().'.'.$profileImageFile->guessExtension();
                            
                            try {
                                $profileImageFile->move(
                                    $this->getParameter('hotel_profiles_directory'),
                                    $newFilename
                                );
                                
                                // Supprimer l'ancienne image si elle existe
                                if ($hotel->getProfileImageUrl()) {
                                    $oldImagePath = $this->getParameter('hotel_profiles_directory').'/'.$hotel->getProfileImageUrl();
                                    if (file_exists($oldImagePath)) {
                                        unlink($oldImagePath);
                                    }
                                }
                                
                                $hotel->setProfileImageUrl($newFilename);
                            } catch (FileException $e) {
                                $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image : ' . $e->getMessage());
                            }
                        }
                    }
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'hotel' => $hotel,
            'hotel_profiles_directory' => $this->getParameter('hotel_profiles_directory'),
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
