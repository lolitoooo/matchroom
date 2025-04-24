<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Entity\RoomType;
use App\Form\AdvancedSearchType;
use App\Repository\HotelRepository;
use App\Repository\RoomTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/search')]
class SearchController extends AbstractController
{
    #[Route('/', name: 'app_search')]
    public function index(Request $request, HotelRepository $hotelRepository, RoomTypeRepository $roomTypeRepository): Response
    {
        $form = $this->createForm(AdvancedSearchType::class);
        $form->handleRequest($request);
        
        $results = [];
        
        // Récupérer les paramètres de recherche, même pour la page d'accueil
        $data = $form->getData() ?: [];
        $destination = $data['destination'] ?? $request->query->get('destination');
        $startDate = $data['startDate'] ?? null;
        $endDate = $data['endDate'] ?? null;
        $budget = $data['budget'] ?? $request->query->get('budget');
        
        // Appliquer les filtres si des paramètres sont présents
        if ($destination || $startDate || $endDate || $budget) {
            $results = $roomTypeRepository->findBySearchCriteria(
                $destination,
                $startDate,
                $endDate,
                null, // Type de chambre retiré
                $budget
            );
        } else {
            // Afficher toutes les chambres disponibles par défaut
            $results = $roomTypeRepository->findBy(['available' => true], ['basePrice' => 'ASC']);
        }
        
        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'results' => $results,
        ]);
    }
    
    #[Route('/swipe', name: 'app_search_swipe')]
    public function swipe(Request $request, RoomTypeRepository $roomTypeRepository): Response
    {
        // Récupérer les chambres disponibles pour le swipe
        $roomTypes = $roomTypeRepository->findBy(['available' => true], ['id' => 'ASC'], 20);
        
        return $this->render('search/swipe.html.twig', [
            'roomTypes' => $roomTypes,
        ]);
    }
    
    #[Route('/map', name: 'app_search_map')]
    public function map(Request $request, HotelRepository $hotelRepository): Response
    {
        $hotels = $hotelRepository->findAll();
        
        // Préparer les données des hôtels pour la carte
        $hotelsData = [];
        foreach ($hotels as $hotel) {
            $hotelsData[] = [
                'id' => $hotel->getId(),
                'name' => $hotel->getName(),
                'latitude' => $hotel->getLatitude(),
                'longitude' => $hotel->getLongitude(),
                'minPrice' => $this->getMinPrice($hotel),
            ];
        }
        
        return $this->render('search/map.html.twig', [
            'hotels' => $hotelsData,
        ]);
    }
    
    #[Route('/api/hotels', name: 'app_api_hotels')]
    public function apiHotels(Request $request, HotelRepository $hotelRepository): JsonResponse
    {
        $bounds = [
            'north' => $request->query->get('north'),
            'south' => $request->query->get('south'),
            'east' => $request->query->get('east'),
            'west' => $request->query->get('west'),
        ];
        
        $hotels = $hotelRepository->findByBounds($bounds);
        
        $hotelsData = [];
        foreach ($hotels as $hotel) {
            $hotelsData[] = [
                'id' => $hotel->getId(),
                'name' => $hotel->getName(),
                'latitude' => $hotel->getLatitude(),
                'longitude' => $hotel->getLongitude(),
                'minPrice' => $this->getMinPrice($hotel),
            ];
        }
        
        return new JsonResponse($hotelsData);
    }
    
    #[Route('/api/hotels/draw', name: 'app_api_hotels_draw')]
    public function apiHotelsDraw(Request $request, HotelRepository $hotelRepository): JsonResponse
    {
        $polygon = json_decode($request->getContent(), true);
        
        $hotels = $hotelRepository->findByPolygon($polygon);
        
        $hotelsData = [];
        foreach ($hotels as $hotel) {
            $hotelsData[] = [
                'id' => $hotel->getId(),
                'name' => $hotel->getName(),
                'latitude' => $hotel->getLatitude(),
                'longitude' => $hotel->getLongitude(),
                'minPrice' => $this->getMinPrice($hotel),
            ];
        }
        
        return new JsonResponse($hotelsData);
    }
    
    private function getMinPrice(Hotel $hotel): ?float
    {
        $minPrice = null;
        foreach ($hotel->getRoomTypes() as $roomType) {
            $price = (float) $roomType->getBasePrice();
            if ($minPrice === null || $price < $minPrice) {
                $minPrice = $price;
            }
        }
        
        return $minPrice;
    }
}
