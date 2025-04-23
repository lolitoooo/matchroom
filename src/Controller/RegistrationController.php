<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Hotel;
use App\Repository\UserRepository;
use App\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RegistrationController extends AbstractController
{
    #[Route('/register/client', name: 'app_register_client')]
    public function registerClient(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager, 
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage
    ): Response
    {
        // Si user connecté, rediriger vers la page d'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        
        $error = null;
        
        if ($request->isMethod('POST')) {
            $firstname = $request->request->get('firstname');
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            $agreeTerms = $request->request->get('agreeTerms');
            
            if ($password !== $confirmPassword) {
                $error = 'Les mots de passe ne correspondent pas';
            } else if (!$agreeTerms) {
                $error = 'Vous devez accepter les conditions d\'utilisation';
            } else {
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $error = 'Cette adresse email est déjà utilisée';
                } else {
                    $user = new User();
                    $user->setFirstname($firstname);
                    $user->setName($name);
                    $user->setEmail($email);
                    $user->setPhone($phone);
                    $user->setRoles(['ROLE_USER']);
                    
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);
                    
                    $entityManager->persist($user);
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
                    return $this->redirectToRoute('app_login');
                }
            }
        }
        
        return $this->render('registration/register_client.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/register/hotelier', name: 'app_register_hotelier')]
    public function registerHotelier(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $entityManager, 
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        
        $error = null;
        
        if ($request->isMethod('POST')) {
            $firstname = $request->request->get('firstname');
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            $agreeTerms = $request->request->get('agreeTerms');
            
            if ($password !== $confirmPassword) {
                $error = 'Les mots de passe ne correspondent pas';
            } else if (!$agreeTerms) {
                $error = 'Vous devez accepter les conditions d\'utilisation';
            } else {
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $error = 'Cette adresse email est déjà utilisée';
                } else {
                    $user = new User();
                    $user->setFirstname($firstname);
                    $user->setName($name);
                    $user->setEmail($email);
                    $user->setPhone($phone);
                    $user->setRoles(['ROLE_HOTEL']);
                    
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);
                    
                    $entityManager->persist($user);
                    
                    $hotel = new Hotel();
                    $hotel->setName($name);
                    $hotel->setUser($user);
                    $hotel->setAddress('À compléter');
                    $hotel->setLatitude('0');
                    $hotel->setLongitude('0');
                    
                    $entityManager->persist($hotel);
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Votre compte hôtelier a été créé avec succès ! Vous pouvez maintenant vous connecter.');
                    return $this->redirectToRoute('app_login');
                }
            }
        }
        
        return $this->render('registration/register_hotelier.html.twig', [
            'error' => $error,
        ]);
    }
}
