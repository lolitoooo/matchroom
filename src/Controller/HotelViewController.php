<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Repository\HotelRepository;
use App\Repository\RoomTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HotelViewController extends AbstractController
{
    #[Route('/hotel/{id}', name: 'app_hotel_view')]
    public function view(Hotel $hotel, RoomTypeRepository $roomTypeRepository): Response
    {
        // Récupérer toutes les chambres de l'hôtel
        $roomTypes = $roomTypeRepository->findBy(['hotel' => $hotel]);
        
        return $this->render('hotel/view.html.twig', [
            'hotel' => $hotel,
            'roomTypes' => $roomTypes,
            'hotel_profiles_directory' => $this->getParameter('hotel_profiles_directory')
        ]);
    }
}
