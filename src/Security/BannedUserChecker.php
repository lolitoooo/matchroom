<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class BannedUserChecker
{
    private $tokenStorage;
    private $urlGenerator;
    private $requestStack;

    public function __construct(
        TokenStorageInterface $tokenStorage, 
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Vérifier si l'utilisateur est banni
        if ($user->isBanned()) {
            // Ignorer les routes spécifiques (page de contact, page d'erreur banni, déconnexion)
            $route = $event->getRequest()->attributes->get('_route');
            $allowedRoutes = ['app_banned', 'app_contact', 'app_logout'];
            
            if (!in_array($route, $allowedRoutes)) {
                // Ajouter un message flash
                $session = $this->requestStack->getSession();
                $session->getFlashBag()->add('danger', 'Votre compte a été suspendu. Si vous pensez qu\'il s\'agit d\'une erreur, veuillez nous contacter.');
                
                // Rediriger vers notre page d'erreur personnalisée
                $response = new RedirectResponse($this->urlGenerator->generate('app_banned'));
                $event->setResponse($response);
            }
        }
    }
}
