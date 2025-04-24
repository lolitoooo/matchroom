<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/legal', name: 'app_legal_')]
class LegalController extends AbstractController
{
    #[Route('/terms', name: 'terms')]
    public function terms(): Response
    {
        return $this->render('legal/terms.html.twig');
    }

    #[Route('/privacy', name: 'privacy')]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }

    #[Route('/legal-notice', name: 'notice')]
    public function legalNotice(): Response
    {
        return $this->render('legal/legal_notice.html.twig');
    }
}
