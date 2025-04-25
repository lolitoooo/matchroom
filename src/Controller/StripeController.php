<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Negotiation;
use App\Entity\Payment;
use App\Repository\NegotiationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

#[Route('/stripe')]
class StripeController extends AbstractController
{
    private $stripeSecretKey;
    private $stripeWebhookSecret;
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $stripeKey
    )
    {
        $this->stripeSecretKey = $stripeKey;
    }
    
    #[Route('/checkout/{id}', name: 'app_stripe_checkout')]
    public function checkout(Negotiation $negotiation): Response
    {
        $user = $this->getUser();
        if (!$user || $negotiation->getClient() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à effectuer ce paiement.');
        }
        
        if ($negotiation->getStatus() !== Negotiation::STATUS_ACCEPTED) {
            $this->addFlash('error', 'Cette négociation n\'a pas été acceptée et ne peut pas être payée.');
            return $this->redirectToRoute('app_negotiation_status', ['id' => $negotiation->getId()]);
        }
        
        // Calcul du montant total
        $nights = $negotiation->getEndDate()->diff($negotiation->getStartDate())->days;
        $totalAmount = $negotiation->getProposedPrice() * $nights;
        
        // Calcul du montant HT et de la TVA (20%)
        $amountHT = round($totalAmount / 1.2, 2);
        $amountTVA = round($totalAmount - $amountHT, 2);
        
        // Initialiser Stripe
        Stripe::setApiKey($this->stripeSecretKey);
        
        // Créer une session de paiement Stripe
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $negotiation->getRoomType()->getName() . ' - ' . $negotiation->getRoomType()->getHotel()->getName(),
                        'description' => 'Du ' . $negotiation->getStartDate()->format('d/m/Y') . ' au ' . $negotiation->getEndDate()->format('d/m/Y') . 
                                     ' - Prix TTC: ' . number_format($totalAmount, 2, ',', ' ') . '€ (HT: ' . 
                                     number_format($amountHT, 2, ',', ' ') . '€, TVA 20%: ' . 
                                     number_format($amountTVA, 2, ',', ' ') . '€)',
                    ],
                    'unit_amount' => (int)($totalAmount * 100), // Montant en centimes
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_stripe_success', ['id' => $negotiation->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_stripe_cancel', ['id' => $negotiation->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'client_reference_id' => $negotiation->getId(),
        ]);
        
        return $this->redirect($session->url);
    }
    
    #[Route('/success/{id}', name: 'app_stripe_success')]
    public function success(Negotiation $negotiation): Response
    {
        // Créer une réservation confirmée
        $booking = new Booking();
        $booking->setNegotiation($negotiation);
        $booking->setStartDate($negotiation->getStartDate());
        $booking->setEndDate($negotiation->getEndDate());
        $booking->setStatus(Booking::STATUS_CONFIRMED);
        
        // Calcul du montant total, HT et TVA
        $nights = $negotiation->getEndDate()->diff($negotiation->getStartDate())->days;
        $totalAmount = $negotiation->getProposedPrice() * $nights;
        $amountHT = round($totalAmount / 1.2, 2);
        $amountTVA = round($totalAmount - $amountHT, 2);
        
        // Créer un paiement
        $payment = new Payment();
        $payment->setBooking($booking);
        $payment->setAmount($totalAmount);
        $payment->setStatus('completed');
        $payment->setPaymentMethod('stripe');
        $payment->setCreatedAt(new \DateTime());
        
        // Annuler les réservations en attente qui chevauchent cette période
        $this->cancelOverlappingBookings($negotiation->getRoomType()->getId(), $booking->getStartDate(), $booking->getEndDate());
        
        $this->entityManager->persist($booking);
        $this->entityManager->persist($payment);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Paiement réussi ! Votre réservation est confirmée.');
        
        return $this->render('stripe/success.html.twig', [
            'booking' => $booking,
            'negotiation' => $negotiation,
        ]);
    }
    
    #[Route('/cancel/{id}', name: 'app_stripe_cancel')]
    public function cancel(Negotiation $negotiation): Response
    {
        $this->addFlash('info', 'Le paiement a été annulé. Votre réservation n\'a pas été confirmée.');
        
        return $this->redirectToRoute('app_negotiation_status', ['id' => $negotiation->getId()]);
    }
    
    // Webhook supprimé pour simplifier l'intégration
    
    /**
     * Annule toutes les réservations en attente qui chevauchent une période donnée
     */
    private function cancelOverlappingBookings(int $roomTypeId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): void
    {
        $bookingRepository = $this->entityManager->getRepository(Booking::class);
        
        // Trouver toutes les réservations en attente qui chevauchent la période
        $overlappingBookings = $bookingRepository->findPendingBookingsOverlappingPeriod(
            $roomTypeId,
            $startDate,
            $endDate
        );
        
        // Annuler ces réservations
        foreach ($overlappingBookings as $booking) {
            $booking->setStatus(Booking::STATUS_CANCELLED);
            $this->entityManager->persist($booking);
        }
        
        // Pas besoin de flush ici, cela sera fait dans la méthode appelante
    }
}
