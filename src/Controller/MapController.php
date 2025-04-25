<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Repository\HotelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MapController extends AbstractController
{
    #[Route('/map', name: 'app_map')]
    public function index(HotelRepository $hotelRepository): Response
    {
        return $this->render('map/index.html.twig', [
            'controller_name' => 'MapController',
        ]);
    }

    #[Route('/map/hotels', name: 'app_map_hotels_data', methods: ['GET'])]
    public function getHotelsData(HotelRepository $hotelRepository): JsonResponse
    {
        $hotels = $hotelRepository->findAll();
        
        $hotelsData = [];
        foreach ($hotels as $hotel) {
            if ($hotel->getLatitude() && $hotel->getLongitude()) {
                $hotelsData[] = [
                    'id' => $hotel->getId(),
                    'name' => $hotel->getName(),
                    'address' => $hotel->getAddress(),
                    'description' => $hotel->getDescription(),
                    'latitude' => $hotel->getLatitude(),
                    'longitude' => $hotel->getLongitude(),
                    'profileImageUrl' => $hotel->getProfileImageUrl(),
                ];
            }
        }
        
        return new JsonResponse($hotelsData);
    }
}
