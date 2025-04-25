<?php

namespace App\Controller;

use App\Form\AdvancedSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        // Créer le formulaire de recherche avancée
        $form = $this->createForm(AdvancedSearchType::class);
        $form->handleRequest($request);
        
        // Rediriger vers la page de recherche si le formulaire est soumis
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Construire les paramètres de requête pour la redirection
            $queryParams = [];
            
            if (!empty($data['destination'])) {
                $queryParams['destination'] = $data['destination'];
            }
            
            if (!empty($data['startDate'])) {
                $queryParams['startDate'] = $data['startDate']->format('Y-m-d');
            }
            
            if (!empty($data['endDate'])) {
                $queryParams['endDate'] = $data['endDate']->format('Y-m-d');
            }
            
            if (!empty($data['persons'])) {
                $queryParams['persons'] = $data['persons'];
            }
            
            // Rediriger vers la page de recherche avec les paramètres
            return $this->redirectToRoute('app_search', $queryParams);
        }
        
        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
