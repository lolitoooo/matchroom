<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Entity\RoomPhoto;
use App\Entity\RoomType;
use App\Form\RoomPhotoType;
use App\Form\RoomType as RoomTypeForm;
use App\Repository\HotelRepository;
use App\Repository\RoomTypeRepository;
use App\Repository\NegotiationRepository;
use App\Entity\Negotiation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/hotel/rooms')]
class HotelRoomController extends AbstractController
{
    #[Route('/', name: 'app_hotel_rooms')]
    public function index(EntityManagerInterface $entityManager, HotelRepository $hotelRepository): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_HOTEL', $user->getRoles())) {
            throw new AccessDeniedException('Vous devez être connecté en tant qu\'hôtelier pour accéder à cette page.');
        }

        $hotel = $hotelRepository->findOneBy(['user' => $user]);
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé.');
        }
        
        // Récupérer directement les types de chambres depuis la base de données
        $roomTypeRepository = $entityManager->getRepository(RoomType::class);
        $roomTypes = $roomTypeRepository->findBy(['hotel' => $hotel]);

        return $this->render('hotel_room/index.html.twig', [
            'hotel' => $hotel,
            'roomTypes' => $roomTypes,
        ]);
    }

    #[Route('/new', name: 'app_hotel_room_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, HotelRepository $hotelRepository, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_HOTEL', $user->getRoles())) {
            throw new AccessDeniedException('Vous devez être connecté en tant qu\'hôtelier pour accéder à cette page.');
        }

        $hotel = $hotelRepository->findOneBy(['user' => $user]);
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé.');
        }

        $roomType = new RoomType();
        $roomType->setHotel($hotel);
        $form = $this->createForm(RoomTypeForm::class, $roomType);
        $photoForm = $this->createForm(RoomPhotoType::class, new RoomPhoto());
        
        // Ajout des champs d'équipements par catégorie
        $formBuilder = $form->getConfig()->getFormFactory()->createNamedBuilder('', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\FormType', $roomType, [
            'csrf_protection' => false,
            'mapped' => false,
        ]);
        
        // Définir les catégories d'équipements directement ici
        $amenitiesCategories = [
            'Salle de bain' => [
                'Salle de bain' => 'bathroom',
                'Sèche-cheveux' => 'hairdryer',
                'Produits de nettoyage' => 'cleaning_products',
                'Shampoing vega' => 'vegan_shampoo',
                'Savon pour le corps vega' => 'vegan_soap',
                'Eau chaude' => 'hot_water',
                'Gel douche' => 'shower_gel',
            ],
            'Chambre et linge' => [
                'Équipements de base (serviettes, draps, savon, papier toilette)' => 'basic_essentials',
                'Cintres' => 'hangers',
                'Draps' => 'sheets',
                'Oreillers et couvertures supplémentaires' => 'extra_pillows_blankets',
                'Stores' => 'blinds',
                'Fer à repasser' => 'iron',
                'Espace de rangement pour les vêtements' => 'clothing_storage',
            ],
            'Divertissement' => [
                'Connexion Ethernet' => 'ethernet',
                'TV HD avec Netflix' => 'hd_tv_netflix',
            ],
            'Chauffage et climatisation' => [
                'Ventilateurs portables' => 'portable_fans',
                'Chauffage' => 'heating',
            ],
            'Securite' => [
                'Détecteur de fumée' => 'smoke_detector',
            ],
            'Internet et bureau' => [
                'Wifi' => 'wifi',
                'Wi-Fi portable' => 'portable_wifi',
            ],
            'Cuisine et salle a manger' => [
                'Cuisine' => 'kitchen',
                'Réfrigérateur' => 'refrigerator',
                'Four à micro-ondes' => 'microwave',
                'Équipements de cuisine de base' => 'basic_kitchen_equipment',
                'Vaisselle et couverts' => 'dishes_silverware',
                'Mini réfrigérateur' => 'mini_fridge',
                'Cuisinière à induction' => 'induction_stove',
                'Bouilloire électrique' => 'electric_kettle',
                'Cafetière' => 'coffee_maker',
                'Verres à vin' => 'wine_glasses',
                'Grille-pain' => 'toaster',
            ],
            'Caracteristiques de l_emplacement' => [
                'Front de mer' => 'waterfront',
                'Accès plage ou bord de mer' => 'beach_access',
                'Entrée privée' => 'private_entrance',
                'Laverie automatique à proximité' => 'laundromat_nearby',
            ],
            'Exterieur' => [
                'Arrière-cour' => 'backyard',
            ],
            'Parking et installations' => [
                'Allée de stationnement gratuite sur place' => 'free_parking',
            ],
            'Services' => [
                'Arrivée autonome' => 'self_check_in',
                'Boîte à clé sécurisée' => 'lockbox',
            ],
            'Non inclus' => [
                'Caméras de surveillance extérieures présentes sur place' => 'outdoor_surveillance_cameras',
                'Lave-linge' => 'washer',
                'Sèche-linge' => 'dryer',
                'Climatisation' => 'air_conditioning',
                'Détecteur de monoxyde de carbone' => 'carbon_monoxide_detector',
            ],
        ];
        
        // Ajouter les champs pour chaque catégorie
        foreach ($amenitiesCategories as $category => $items) {
            $categoryKey = 'amenities_' . strtolower(str_replace([' ', "'", 'é', 'è', 'à', 'ê', 'î', 'ô', 'û', 'ç'], ['_', '_', 'e', 'e', 'a', 'e', 'i', 'o', 'u', 'c'], $category));
            
            $formBuilder->add($categoryKey, 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType', [
                'label' => $category,
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'mapped' => false,
                'choices' => array_flip($items),
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'h-4 w-4 text-primary-600 focus:ring-primary-500 border-primary-300 rounded'];
                },
                'label_attr' => [
                    'class' => 'font-medium text-primary-700 text-base mb-2 block',
                ],
                'row_attr' => [
                    'class' => 'bg-primary-50 p-4 rounded-lg mb-4',
                ],
            ]);
        }
        
        $amenitiesForm = $formBuilder->getForm();
        
        $form->handleRequest($request);
        $amenitiesForm->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement des équipements par catégorie
            $amenities = [];
            
            // Créer un tableau pour convertir les codes en noms français
            $codeToName = [];
            foreach ($amenitiesCategories as $category => $items) {
                foreach ($items as $name => $code) {
                    $codeToName[$code] = $name;
                }
            }
            
            // Récupérer les équipements sélectionnés
            $formData = $request->request->all();
            
            foreach ($amenitiesCategories as $category => $items) {
                $categoryKey = 'amenities_' . strtolower(str_replace([' ', "'", 'é', 'è', 'à', 'ê', 'î', 'ô', 'û', 'ç'], ['_', '_', 'e', 'e', 'a', 'e', 'i', 'o', 'u', 'c'], $category));
                
                if (isset($formData[$categoryKey]) && is_array($formData[$categoryKey])) {
                    foreach ($formData[$categoryKey] as $code) {
                        // Utiliser le nom français au lieu du code
                        if (isset($codeToName[$code])) {
                            $amenities[] = $codeToName[$code];
                        } else {
                            $amenities[] = $code; // Fallback au cas où
                        }
                    }
                }
            }
            
            // Mettre à jour les équipements
            $roomType->setAmenities($amenities);
            
            // Gestion des photos
            $photos = $form->get('photos')->getData();
            if ($photos) {
                foreach ($photos as $photoFile) {
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                    try {
                        $photoFile->move(
                            $this->getParameter('room_photos_directory'),
                            $newFilename
                        );

                        $roomPhoto = new RoomPhoto();
                        $roomPhoto->setUrl($newFilename);
                        $roomPhoto->setAlt($roomType->getName() . ' - Photo ' . (count($roomType->getRoomPhotos()) + 1));
                        $roomPhoto->setRoomType($roomType);
                        $roomPhoto->setDisplayOrder(count($roomType->getRoomPhotos()) + 1);
                        
                        $entityManager->persist($roomPhoto);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement d\'une photo : ' . $e->getMessage());
                    }
                }
            }
            
            // Enregistrer les modifications
            $entityManager->persist($roomType);
            $entityManager->flush();

            $this->addFlash('success', 'La chambre a été créée avec succès.');
            return $this->redirectToRoute('app_hotel_rooms');
        }

        return $this->render('hotel_room/new.html.twig', [
            'roomType' => $roomType,
            'form' => $form->createView(),
            'photoForm' => $photoForm->createView(),
            'amenitiesForm' => $amenitiesForm->createView(),
            'amenities_categories' => $amenitiesCategories,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_hotel_room_edit')]
    public function edit(Request $request, RoomType $roomType, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_HOTEL', $user->getRoles())) {
            throw new AccessDeniedException('Vous devez être connecté en tant qu\'hôtelier pour accéder à cette page.');
        }

        $hotel = $roomType->getHotel();
        if ($hotel->getUser() !== $user) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à modifier cette chambre.');
        }

        $form = $this->createForm(RoomTypeForm::class, $roomType);
        $photoForm = $this->createForm(RoomPhotoType::class, new RoomPhoto());
        
        // Ajout des champs d'équipements par catégorie
        $formBuilder = $form->getConfig()->getFormFactory()->createNamedBuilder('', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\FormType', $roomType, [
            'csrf_protection' => false,
            'mapped' => false,
        ]);
        
        // Définir les catégories d'équipements directement ici
        $amenitiesCategories = [
            'Salle de bain' => [
                'Salle de bain' => 'bathroom',
                'Sèche-cheveux' => 'hairdryer',
                'Produits de nettoyage' => 'cleaning_products',
                'Shampoing végétal' => 'vegan_shampoo',
                'Savon pour le corps végétal' => 'vegan_soap',
                'Eau chaude' => 'hot_water',
                'Gel douche' => 'shower_gel',
            ],
            'Chambre et linge' => [
                'Équipements de base (serviettes, draps, savon, papier toilette)' => 'basic_essentials',
                'Cintres' => 'hangers',
                'Draps' => 'sheets',
                'Oreillers et couvertures supplémentaires' => 'extra_pillows_blankets',
                'Stores' => 'blinds',
                'Fer à repasser' => 'iron',
                'Espace de rangement pour les vêtements' => 'clothing_storage',
            ],
            'Divertissement' => [
                'Connexion Ethernet' => 'ethernet',
                'TV HD avec Netflix' => 'hd_tv_netflix',
            ],
            'Chauffage et climatisation' => [
                'Ventilateurs portables' => 'portable_fans',
                'Chauffage' => 'heating',
            ],
            'Sécurité' => [
                'Détecteur de fumée' => 'smoke_detector',
            ],
            'Internet et bureau' => [
                'Wifi' => 'wifi',
                'Wi-Fi portable' => 'portable_wifi',
            ],
            'Cuisine et salle à manger' => [
                'Cuisine' => 'kitchen',
                'Réfrigérateur' => 'refrigerator',
                'Four à micro-ondes' => 'microwave',
                'Équipements de cuisine de base' => 'basic_kitchen_equipment',
                'Vaisselle et couverts' => 'dishes_silverware',
                'Mini réfrigérateur' => 'mini_fridge',
                'Cuisinière à induction' => 'induction_stove',
                'Bouilloire électrique' => 'electric_kettle',
                'Cafetière' => 'coffee_maker',
                'Verres à vin' => 'wine_glasses',
                'Grille-pain' => 'toaster',
            ],
            'Caractéristiques de l\'emplacement' => [
                'Front de mer' => 'waterfront',
                'Accès plage ou bord de mer' => 'beach_access',
                'Entrée privée' => 'private_entrance',
                'Laverie automatique à proximité' => 'laundromat_nearby',
            ],
            'Extérieur' => [
                'Arrière-cour' => 'backyard',
            ],
            'Parking et installations' => [
                'Allée de stationnement gratuite sur place' => 'free_parking',
            ],
            'Services' => [
                'Arrivée autonome' => 'self_check_in',
                'Boîte à clé sécurisée' => 'lockbox',
            ],
            'Non inclus' => [
                'Caméras de surveillance extérieures présentes sur place' => 'outdoor_surveillance_cameras',
                'Lave-linge' => 'washer',
                'Sèche-linge' => 'dryer',
                'Climatisation' => 'air_conditioning',
                'Détecteur de monoxyde de carbone' => 'carbon_monoxide_detector',
            ],
        ];
        
        // Ajouter les champs pour chaque catégorie
        foreach ($amenitiesCategories as $category => $items) {
            $categoryKey = 'amenities_' . strtolower(str_replace([' ', "'", 'é', 'è', 'à', 'ê', 'î', 'ô', 'û', 'ç'], ['_', '_', 'e', 'e', 'a', 'e', 'i', 'o', 'u', 'c'], $category));
            
            $formBuilder->add($categoryKey, 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType', [
                'label' => $category,
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'mapped' => false,
                'choices' => array_flip($items),
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'h-4 w-4 text-primary-600 focus:ring-primary-500 border-primary-300 rounded'];
                },
                'label_attr' => [
                    'class' => 'font-medium text-primary-700 text-base mb-2 block',
                ],
                'row_attr' => [
                    'class' => 'bg-primary-50 p-4 rounded-lg mb-4',
                ],
            ]);
        }
        
        // Initialisation des valeurs des équipements
        $existingAmenities = $roomType->getAmenities() ?? [];
        foreach ($amenitiesCategories as $category => $items) {
            $categoryKey = 'amenities_' . strtolower(str_replace([' ', "'", 'é', 'è', 'à', 'ê', 'î', 'ô', 'û', 'ç'], ['_', '_', 'e', 'e', 'a', 'e', 'i', 'o', 'u', 'c'], $category));
            $categoryAmenities = [];
            
            foreach ($items as $label => $value) {
                if (in_array($label, $existingAmenities)) {
                    $categoryAmenities[] = $label;
                }
            }
            
            if ($formBuilder->has($categoryKey)) {
                $formBuilder->get($categoryKey)->setData($categoryAmenities);
            }
        }
        
        $amenitiesForm = $formBuilder->getForm();
        
        // Traiter la soumission du formulaire
        $form->handleRequest($request);
        $amenitiesForm->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données du formulaire
            $data = $form->getData();
            
            // Mettre à jour les champs de base
            $roomType->setName($data->getName());
            $roomType->setDescription($data->getDescription());
            $roomType->setBasePrice($data->getBasePrice());
            $roomType->setAvailable($data->isAvailable());
            $roomType->setCapacity($data->getCapacity());
            $roomType->setRoomCount($data->getRoomCount());
            $roomType->setBedCount($data->getBedCount());
            
            // Traitement des équipements par catégorie
            $amenities = [];
            
            // Créer un tableau pour convertir les codes en noms français
            $codeToName = [];
            foreach ($amenitiesCategories as $category => $items) {
                foreach ($items as $name => $code) {
                    $codeToName[$code] = $name;
                }
            }
            
            // Récupérer les équipements sélectionnés
            foreach ($amenitiesCategories as $category => $items) {
                $categoryKey = 'amenities_' . strtolower(str_replace([' ', "'", 'é', 'è', 'à', 'ê', 'î', 'ô', 'û', 'ç'], ['_', '_', 'e', 'e', 'a', 'e', 'i', 'o', 'u', 'c'], $category));
                
                if ($request->request->has($categoryKey)) {
                    $selectedItems = $request->request->all($categoryKey);
                    foreach ($selectedItems as $code) {
                        // Utiliser le nom français au lieu du code
                        if (isset($codeToName[$code])) {
                            $amenities[] = $codeToName[$code];
                        } else {
                            $amenities[] = $code; // Fallback au cas où
                        }
                    }
                }
            }
            
            $roomType->setAmenities($amenities);
            
            // Traiter les photos
            $photoFiles = $form->get('photos')->getData();
            
            if ($photoFiles) {
                foreach ($photoFiles as $photoFile) {
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();
                    
                    try {
                        $photoFile->move(
                            $this->getParameter('room_photos_directory'),
                            $newFilename
                        );
                        
                        $photo = new RoomPhoto();
                        $photo->setRoomType($roomType);
                        $photo->setUrl($newFilename);
                        $photo->setFilename($newFilename); // Ajouter également le filename pour compatibilité
                        $photo->setDisplayOrder(count($roomType->getRoomPhotos()) + 1);
                        
                        $entityManager->persist($photo);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement d\'une photo.');
                    }
                }
            }
            
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Les modifications ont été enregistrées avec succès.');
                return $this->redirectToRoute('app_hotel_rooms');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de la chambre: ');
                // On reste sur la page d'édition en cas d'erreur
                return $this->render('hotel_room/edit.html.twig', [
                    'roomType' => $roomType,
                    'form' => $form->createView(),
                    'amenitiesForm' => $amenitiesForm->createView(),
                    'amenities_categories' => $amenitiesCategories,
                ]);
            }
        }

        return $this->render('hotel_room/edit.html.twig', [
            'roomType' => $roomType,
            'form' => $form->createView(),
            'photoForm' => $photoForm->createView(),
            'amenitiesForm' => $amenitiesForm->createView(),
            'amenities_categories' => $amenitiesCategories,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_hotel_room_delete', methods: ['POST'])]
    public function delete(Request $request, RoomType $roomType, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_HOTEL', $user->getRoles())) {
            throw new AccessDeniedException('Vous devez être connecté en tant qu\'hôtelier pour accéder à cette page.');
        }

        $hotel = $roomType->getHotel();
        if ($hotel->getUser() !== $user) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette chambre.');
        }

        if ($this->isCsrfTokenValid('delete'.$roomType->getId(), $request->request->get('_token'))) {
            try {
                // Vérifier s'il y a des négociations associées
                $negotiations = $roomType->getNegotiations();
                
                if (count($negotiations) > 0) {
                    $this->addFlash('error', 'Impossible de supprimer cette chambre car elle a des négociations associées. Désactivez-la plutôt en décochant "Disponible à la réservation".');
                    return $this->redirectToRoute('app_hotel_rooms');
                }
                
                $entityManager->remove($roomType);
                $entityManager->flush();
                $this->addFlash('success', 'La chambre a été supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cette chambre car elle est utilisée dans des négociations ou réservations. Désactivez-la plutôt en décochant "Disponible à la réservation".');
                return $this->redirectToRoute('app_hotel_rooms');
            }
        }

        return $this->redirectToRoute('app_hotel_rooms');
    }

    #[Route('/{id}/add-photo', name: 'app_hotel_room_add_photo', methods: ['POST'])]
    public function addPhoto(Request $request, RoomType $roomType, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_HOTEL', $user->getRoles())) {
            throw new AccessDeniedException('Vous devez être connecté en tant qu\'hôtelier pour accéder à cette page.');
        }

        $hotel = $roomType->getHotel();
        if ($hotel->getUser() !== $user) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à modifier cette chambre.');
        }

        // Traiter directement le fichier uploadé sans utiliser le formulaire
        $photoFile = $request->files->get('photo');

        if ($photoFile) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

            try {
                $photoFile->move(
                    $this->getParameter('room_photos_directory'),
                    $newFilename
                );

                $roomPhoto = new RoomPhoto();
                $roomPhoto->setUrl($newFilename);
                $roomPhoto->setFilename($newFilename); // Ajouter également le filename pour compatibilité
                $roomPhoto->setRoomType($roomType);
                $roomPhoto->setDisplayOrder(count($roomType->getRoomPhotos()) + 1);
                
                $entityManager->persist($roomPhoto);
                $entityManager->flush();

                $this->addFlash('success', 'La photo a été ajoutée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de la photo: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Aucun fichier photo n\'a été fourni.');
        }

        return $this->redirectToRoute('app_hotel_room_edit', ['id' => $roomType->getId()]);
    }

    #[Route('/photo/{id}/delete', name: 'app_hotel_room_photo_delete', methods: ['POST'])]
    public function deletePhoto(Request $request, RoomPhoto $roomPhoto, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_HOTEL', $user->getRoles())) {
            throw new AccessDeniedException('Vous devez être connecté en tant qu\'hôtelier pour accéder à cette page.');
        }

        $roomType = $roomPhoto->getRoomType();
        $hotel = $roomType->getHotel();
        
        if ($hotel->getUser() !== $user) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette photo.');
        }

        // Supprimer la photo sans vérification du token CSRF pour simplifier
        try {
            // Supprimer le fichier physique
            $filePath = $this->getParameter('room_photos_directory').'/'.$roomPhoto->getUrl();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Détacher la photo de la chambre avant de la supprimer
            $roomType = $roomPhoto->getRoomType();
            $roomPhoto->setRoomType(null);
            
            $entityManager->remove($roomPhoto);
            $entityManager->flush();
            
            $this->addFlash('success', 'La photo a été supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de supprimer cette photo: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_hotel_room_edit', ['id' => $roomType->getId()]);
    }

    #[Route('/negotiations', name: 'app_hotel_negotiations')]
    public function hotelNegotiations(
        HotelRepository $hotelRepo,
        RoomTypeRepository $roomRepo,
        NegotiationRepository $negotiationRepo
    ): Response {
        $user = $this->getUser();
        $hotel = $hotelRepo->findOneBy(['user' => $user]);

        if (!$hotel) {
            throw $this->createNotFoundException('Aucun hôtel associé à cet utilisateur.');
        }

        $roomTypes = $roomRepo->findBy(['hotel' => $hotel]);

        // Préparation des négociations par roomId
        $groupedNegotiations = [];
        foreach ($roomTypes as $room) {
            $negotiations = $negotiationRepo->findBy([
                'roomType' => $room,
                'status' => Negotiation::STATUS_PENDING
            ], ['createdAt' => 'DESC']);

            $groupedNegotiations[$room->getId()] = [
                'room' => $room,
                'negotiations' => $negotiations
            ];
        }

        return $this->render('hotel_room/negotiations.html.twig', [
            'roomTypes' => $roomTypes,
            'groupedNegotiations' => $groupedNegotiations,
        ]);
    }



}
