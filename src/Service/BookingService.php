<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Negotiation;
use App\Entity\Payment;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BookingService
{
    private EntityManagerInterface $entityManager;
    private BookingRepository $bookingRepository;
    private EmailService $emailService;
    private StripeClient $stripeClient;
    private string $pdfDirectory;

    public function __construct(
        EntityManagerInterface $entityManager,
        BookingRepository $bookingRepository,
        EmailService $emailService,
        ParameterBagInterface $params,
        string $stripeSecretKey
    ) {
        $this->entityManager = $entityManager;
        $this->bookingRepository = $bookingRepository;
        $this->emailService = $emailService;
        $this->stripeClient = new StripeClient($stripeSecretKey);
        $this->pdfDirectory = $params->get('kernel.project_dir') . '/var/invoices/';
    }

    /**
     * Crée une réservation à partir d'une négociation acceptée
     */
    public function createBooking(Negotiation $negotiation): Booking
    {
        if ($negotiation->getStatus() !== Negotiation::STATUS_ACCEPTED) {
            throw new \InvalidArgumentException("Impossible de créer une réservation pour une négociation non acceptée");
        }

        $booking = new Booking();
        $booking->setNegotiation($negotiation);
        $booking->setStatus(Booking::STATUS_PENDING);

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    /**
     * Crée une session de paiement Stripe pour une réservation
     */
    public function createPaymentSession(Booking $booking): string
    {
        $negotiation = $booking->getNegotiation();
        $roomType = $negotiation->getRoomType();
        $hotel = $roomType->getHotel();
        $client = $negotiation->getClient();

        $checkoutSession = $this->stripeClient->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => "Réservation {$roomType->getName()} - {$hotel->getName()}",
                        'description' => "Du {$negotiation->getStartDate()->format('d/m/Y')} au {$negotiation->getEndDate()->format('d/m/Y')}",
                    ],
                    'unit_amount' => (int) ($negotiation->getProposedPrice() * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => 'https://matchroom.com/booking/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://matchroom.com/booking/cancel?session_id={CHECKOUT_SESSION_ID}',
            'customer_email' => $client->getEmail(),
            'client_reference_id' => $booking->getId(),
        ]);

        $payment = new Payment();
        $payment->setStripeId($checkoutSession->id);
        $payment->setAmount($negotiation->getProposedPrice());
        $payment->setStatus(Payment::STATUS_PENDING);

        $this->entityManager->persist($payment);
        $booking->setPayment($payment);
        $this->entityManager->flush();

        return $checkoutSession->url;
    }

    /**
     * Confirme une réservation après paiement réussi
     */
    public function confirmBooking(string $sessionId): Booking
    {
        $session = $this->stripeClient->checkout->sessions->retrieve($sessionId);
        
        $payment = $this->entityManager->getRepository(Payment::class)->findOneBy(['stripeId' => $sessionId]);
        
        if (!$payment) {
            throw new \Exception("Paiement non trouvé pour la session $sessionId");
        }
        
        $payment->setStatus(Payment::STATUS_COMPLETED);
        $payment->setCompletedAt(new \DateTime());
        
        $booking = $this->entityManager->getRepository(Booking::class)->findOneBy(['payment' => $payment]);
        
        if (!$booking) {
            throw new \Exception("Réservation non trouvée pour le paiement");
        }
        
        $booking->setStatus(Booking::STATUS_CONFIRMED);
        
        $invoicePath = $this->generateInvoice($booking);
        $booking->setInvoicePdf($invoicePath);
        
        $this->entityManager->flush();
        
        $this->emailService->sendBookingConfirmation(
            $booking->getNegotiation()->getClient(),
            $booking->getNegotiation(),
            $invoicePath
        );
        
        return $booking;
    }

    /**
     * Génère une facture PDF pour une réservation
     */
    private function generateInvoice(Booking $booking): string
    {
        if (!file_exists($this->pdfDirectory)) {
            mkdir($this->pdfDirectory, 0777, true);
        }
        
        $negotiation = $booking->getNegotiation();
        $roomType = $negotiation->getRoomType();
        $hotel = $roomType->getHotel();
        $client = $negotiation->getClient();
        
        $filename = 'invoice_' . $booking->getId() . '_' . time() . '.pdf';
        $filepath = $this->pdfDirectory . $filename;
        
        file_put_contents($filepath, "Facture pour la réservation #{$booking->getId()}");
        
        return $filepath;
    }
}
