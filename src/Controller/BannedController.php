<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BannedController extends AbstractController
{
    #[Route('/compte/suspendu', name: 'app_banned')]
    public function index(): Response
    {
        // Ajouter un message flash si ce n'est pas déjà fait
        if (!$this->get('session')->getFlashBag()->has('danger')) {
            $this->addFlash('danger', 'Votre compte a été suspendu. Si vous pensez qu\'il s\'agit d\'une erreur, veuillez nous contacter.');
        }
        
        return $this->render('bundles/TwigBundle/Exception/error_banned.html.twig');
    }
}
