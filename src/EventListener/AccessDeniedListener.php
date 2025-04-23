<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

class AccessDeniedListener implements EventSubscriberInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private RequestStack $requestStack;
    private Security $security;

    public function __construct(
        UrlGeneratorInterface $urlGenerator, 
        RequestStack $requestStack,
        Security $security
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        if ($exception instanceof AccessDeniedException) {
            if ($this->security->getUser()) {
                $this->requestStack->getSession()->getFlashBag()->add('error', 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_home')));
            } else {
                $this->requestStack->getSession()->getFlashBag()->add('error', 'Vous devez être connecté pour accéder à cette page.');
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));
            }
        }
    }
}
