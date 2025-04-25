<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Entity\RoomType;
use App\Entity\RoomPhoto;
use App\Entity\User;
use App\Form\AdminHotelType;
use App\Form\AdminUserType;
use App\Form\AdminRoomType;
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
        // Statistiques pour le tableau de bord
        $userCount = $entityManager->getRepository(User::class)->count([]);
        $hotelCount = $entityManager->getRepository(Hotel::class)->count([]);
        $roomCount = $entityManager->getRepository(RoomType::class)->count([]);
        
        return $this->render('admin/dashboard.html.twig', [
            'user_count' => $userCount,
            'hotel_count' => $hotelCount,
            'room_count' => $roomCount,
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
        $form = $formFactory->create(AdminUserType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
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
        $form = $formFactory->create(AdminUserType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
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
            $user->setIsBanned(true);
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur banni avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/user/{id}/unban', name: 'app_admin_user_unban', methods: ['POST'])]
    public function unbanUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('unban'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsBanned(false);
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur réactivé avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/hotels', name: 'app_admin_hotels')]
    public function hotels(EntityManagerInterface $entityManager): Response
    {
        $hotels = $entityManager->getRepository(Hotel::class)->findAll();
        
        return $this->render('admin/hotels.html.twig', [
            'hotels' => $hotels,
            'hotel_profiles_directory' => $this->getParameter('hotel_profiles_directory')
        ]);
    }
    
    #[Route('/hotel/new', name: 'app_admin_hotel_new')]
    public function newHotel(Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $hotel = new Hotel();
        $form = $formFactory->create(AdminHotelType::class, $hotel, [
            'entity_manager' => $entityManager
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement de l'image de profil
            $profileImageFile = $form->get('profileImage')->getData();
            if ($profileImageFile) {
                $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profileImageFile->guessExtension();
                
                try {
                    $profileImageFile->move(
                        $this->getParameter('hotel_profiles_directory'),
                        $newFilename
                    );
                    
                    $hotel->setProfileImageUrl($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image : ' . $e->getMessage());
                }
            }
            
            $entityManager->persist($hotel);
            $entityManager->flush();
            
            $this->addFlash('success', 'Hôtel créé avec succès !');
            return $this->redirectToRoute('app_admin_hotels');
        }
        
        return $this->render('admin/hotel_form.html.twig', [
            'form' => $form->createView(),
            'hotel' => $hotel,
            'hotel_profiles_directory' => $this->getParameter('hotel_profiles_directory')
        ]);
    }
    
    #[Route('/hotel/{id}/edit', name: 'app_admin_hotel_edit')]
    public function editHotel(Hotel $hotel, Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        // Vérifier si l'image de profil existe physiquement
        if ($hotel->getProfileImageUrl()) {
            $imagePath = $this->getParameter('hotel_profiles_directory').'/'.$hotel->getProfileImageUrl();
            if (!file_exists($imagePath)) {
                // L'image n'existe pas physiquement, on met à null la valeur en base
                $hotel->setProfileImageUrl(null);
                $entityManager->flush();
                $this->addFlash('warning', 'L\'image de profil précédente n\'a pas été trouvée et a été réinitialisée.');
            }
        }
        
        $form = $formFactory->create(AdminHotelType::class, $hotel, [
            'entity_manager' => $entityManager
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement de l'image de profil
            $profileImageFile = $form->get('profileImage')->getData();
            if ($profileImageFile) {
                $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profileImageFile->guessExtension();
                
                try {
                    // Supprimer l'ancienne image si elle existe
                    if ($hotel->getProfileImageUrl()) {
                        $oldImagePath = $this->getParameter('hotel_profiles_directory').'/'.$hotel->getProfileImageUrl();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $profileImageFile->move(
                        $this->getParameter('hotel_profiles_directory'),
                        $newFilename
                    );
                    
                    $hotel->setProfileImageUrl($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image : ' . $e->getMessage());
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Hôtel modifié avec succès !');
            return $this->redirectToRoute('app_admin_hotels');
        }
        
        return $this->render('admin/hotel_form.html.twig', [
            'form' => $form->createView(),
            'hotel' => $hotel,
            'hotel_profiles_directory' => $this->getParameter('hotel_profiles_directory')
        ]);
    }
    
    #[Route('/hotel/{id}/delete', name: 'app_admin_hotel_delete', methods: ['POST'])]
    public function deleteHotel(Hotel $hotel, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$hotel->getId(), $request->request->get('_token'))) {
            // Supprimer l'image de profil si elle existe
            if ($hotel->getProfileImageUrl()) {
                $imagePath = $this->getParameter('hotel_profiles_directory').'/'.$hotel->getProfileImageUrl();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $entityManager->remove($hotel);
            $entityManager->flush();
            
            $this->addFlash('success', 'Hôtel supprimé avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_hotels');
    }
    
    #[Route('/hotel/{id}/rooms', name: 'app_admin_hotel_rooms')]
    public function hotelRooms(Hotel $hotel, EntityManagerInterface $entityManager): Response
    {
        $roomTypes = $entityManager->getRepository(RoomType::class)->findBy(['hotel' => $hotel]);
        
        return $this->render('admin/hotel_rooms.html.twig', [
            'hotel' => $hotel,
            'room_types' => $roomTypes,
        ]);
    }
    
    #[Route('/hotel/{id}/room/new', name: 'app_admin_hotel_room_new')]
    public function newHotelRoom(Hotel $hotel, Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $roomType = new RoomType();
        
        $form = $formFactory->create(AdminRoomType::class, $roomType);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données du formulaire
            $roomType = $form->getData();
            $roomType->setHotel($hotel); // S'assurer que l'hôtel est bien défini

            // Traitement des photos
            $photos = $form->get('photos')->getData();
            foreach ($photos as $photoFile) {
                if ($photoFile) {
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                    try {
                        $photoFile->move(
                            $this->getParameter('room_photos_directory'),
                            $newFilename
                        );

                        $roomPhoto = new RoomPhoto();
                        $roomPhoto->setUrl($newFilename);
                        $roomPhoto->setRoomType($roomType);
                        $roomPhoto->setDisplayOrder(count($roomType->getRoomPhotos()) + 1);
                        $roomPhoto->setAlt($roomType->getName());
                        $entityManager->persist($roomPhoto);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image : ' . $e->getMessage());
                    }
                }
            }

            // Traitement des équipements par catégorie
            $amenities = [];
            $formData = $request->request->all();

            if (isset($formData['admin_room_type'])) {
                foreach ($formData['admin_room_type'] as $key => $value) {
                    if (strpos($key, 'amenities_') === 0 && is_array($value)) {
                        foreach ($value as $amenity) {
                            $amenities[] = $amenity;
                        }
                    }
                }
            }

            // Mettre à jour les équipements
            $roomType->setAmenities($amenities);

            // S'assurer que l'entité est bien gérée par l'EntityManager
            $entityManager->persist($roomType);

            // Enregistrer les modifications dans la base de données
            $entityManager->flush();

            // Vérifier que les données ont bien été enregistrées
            $entityManager->refresh($roomType);

            $this->addFlash('success', 'Chambre ajoutée avec succès !');
            return $this->redirectToRoute('app_admin_hotel_rooms', ['id' => $hotel->getId()]);
        }
        
        return $this->render('admin/hotel_room_form.html.twig', [
            'form' => $form->createView(),
            'hotel' => $hotel,
            'room_type' => $roomType,
        ]);
    }
    
    #[Route('/hotel/{hotelId}/room/{id}/edit', name: 'app_admin_hotel_room_edit')]
    public function editHotelRoom(int $hotelId, int $id, Request $request, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory): Response
    {
        $hotel = $entityManager->getRepository(Hotel::class)->find($hotelId);
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé');
        }
        
        $roomType = $entityManager->getRepository(RoomType::class)->find($id);
        if (!$roomType || $roomType->getHotel()->getId() !== $hotel->getId()) {
            throw $this->createNotFoundException('Chambre non trouvée ou n\'appartient pas à cet hôtel');
        }
        
        $form = $formFactory->create(AdminRoomType::class, $roomType);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données du formulaire
            $roomType = $form->getData();

            // Traitement des photos
            $photos = $form->get('photos')->getData();
            foreach ($photos as $photoFile) {
                if ($photoFile) {
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                    try {
                        $photoFile->move(
                            $this->getParameter('room_photos_directory'),
                            $newFilename
                        );

                        $roomPhoto = new RoomPhoto();
                        $roomPhoto->setUrl($newFilename);
                        $roomPhoto->setRoomType($roomType);
                        $roomPhoto->setDisplayOrder(count($roomType->getRoomPhotos()) + 1);
                        $roomPhoto->setAlt($roomType->getName());
                        $entityManager->persist($roomPhoto);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image : ' . $e->getMessage());
                    }
                }
            }

            // Traitement des équipements par catégorie
            $amenities = [];
            $formData = $request->request->all();

            if (isset($formData['admin_room_type'])) {
                foreach ($formData['admin_room_type'] as $key => $value) {
                    if (strpos($key, 'amenities_') === 0 && is_array($value)) {
                        foreach ($value as $amenity) {
                            $amenities[] = $amenity;
                        }
                    }
                }
            }

            // Mettre à jour les équipements
            $roomType->setAmenities($amenities);

            // S'assurer que l'entité est bien gérée par l'EntityManager
            $entityManager->persist($roomType);

            // Enregistrer les modifications dans la base de données
            $entityManager->flush();

            // Recharger l'entité depuis la base de données pour vérifier que les modifications ont bien été enregistrées
            $entityManager->refresh($roomType);

            $this->addFlash('success', 'Chambre modifiée avec succès !');
            return $this->redirectToRoute('app_admin_hotel_rooms', ['id' => $hotel->getId()]);
        }
        
        return $this->render('admin/hotel_room_form.html.twig', [
            'form' => $form->createView(),
            'hotel' => $hotel,
            'room_type' => $roomType,
        ]);
    }
    
    #[Route('/hotel/room/{id}/delete', name: 'app_admin_hotel_room_delete', methods: ['POST'])]
    public function deleteHotelRoom(RoomType $roomType, Request $request, EntityManagerInterface $entityManager): Response
    {
        $hotelId = $roomType->getHotel()->getId();
        
        if ($this->isCsrfTokenValid('delete'.$roomType->getId(), $request->request->get('_token'))) {
            // Supprimer les photos associées
            foreach ($roomType->getRoomPhotos() as $photo) {
                $imagePath = $this->getParameter('room_photos_directory').'/'.$photo->getUrl();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $entityManager->remove($photo);
            }
            
            $entityManager->remove($roomType);
            $entityManager->flush();
            
            $this->addFlash('success', 'Chambre supprimée avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_hotel_rooms', ['id' => $hotelId]);
    }
    
    #[Route('/hotel/room/photo/{id}/delete', name: 'app_admin_room_photo_delete', methods: ['POST'])]
    public function deleteRoomPhoto(RoomPhoto $roomPhoto, Request $request, EntityManagerInterface $entityManager): Response
    {
        $roomTypeId = $roomPhoto->getRoomType()->getId();
        $hotelId = $roomPhoto->getRoomType()->getHotel()->getId();
        
        if ($this->isCsrfTokenValid('delete'.$roomPhoto->getId(), $request->request->get('_token'))) {
            // Supprimer le fichier physique
            $imagePath = $this->getParameter('room_photos_directory').'/'.$roomPhoto->getUrl();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            
            $entityManager->remove($roomPhoto);
            $entityManager->flush();
            
            $this->addFlash('success', 'Photo supprimée avec succès !');
        }
        
        return $this->redirectToRoute('app_admin_hotel_room_edit', [
            'hotelId' => $hotelId,
            'id' => $roomTypeId,
        ]);
    }
}
