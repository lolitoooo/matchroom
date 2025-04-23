<?php

namespace App\Service;

use App\Entity\Negotiation;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private MailerInterface $mailer;
    private string $senderEmail;

    public function __construct(MailerInterface $mailer, string $senderEmail = 'noreply@matchroom.com')
    {
        $this->mailer = $mailer;
        $this->senderEmail = $senderEmail;
    }

    /**
     * Envoie une notification de nouvelle négociation à l'hôtelier
     */
    public function sendNegotiationNotification(User $hotelier, Negotiation $negotiation): void
    {
        $roomType = $negotiation->getRoomType();
        $hotel = $roomType->getHotel();
        $client = $negotiation->getClient();
        
        $subject = "Nouvelle demande de négociation pour {$roomType->getName()}";
        $content = "
            <h1>Nouvelle demande de négociation</h1>
            <p>Bonjour {$hotelier->getName()},</p>
            <p>Vous avez reçu une nouvelle demande de négociation pour une chambre dans votre hôtel {$hotel->getName()}.</p>
            <p><strong>Détails de la demande :</strong></p>
            <ul>
                <li>Type de chambre : {$roomType->getName()}</li>
                <li>Client : {$client->getName()}</li>
                <li>Date d'arrivée : {$negotiation->getStartDate()->format('d/m/Y')}</li>
                <li>Date de départ : {$negotiation->getEndDate()->format('d/m/Y')}</li>
                <li>Prix proposé : {$negotiation->getProposedPrice()} €</li>
                <li>Prix de base : {$roomType->getBasePrice()} €</li>
            </ul>
            <p>Vous disposez de 3 heures pour répondre à cette demande.</p>
            <p>Connectez-vous à votre compte MatchRoom pour accepter, refuser ou faire une contre-offre.</p>
        ";
        
        $this->sendEmail($hotelier->getEmail(), $subject, $content);
    }

    /**
     * Envoie une notification de réponse à une négociation au client
     */
    public function sendNegotiationResponseNotification(User $client, Negotiation $negotiation): void
    {
        $roomType = $negotiation->getRoomType();
        $hotel = $roomType->getHotel();
        
        $statusText = match($negotiation->getStatus()) {
            Negotiation::STATUS_ACCEPTED => 'acceptée',
            Negotiation::STATUS_REJECTED => 'refusée',
            Negotiation::STATUS_COUNTER_OFFER => 'a reçu une contre-offre',
            default => 'mise à jour'
        };
        
        $subject = "Votre offre pour {$roomType->getName()} a été {$statusText}";
        $content = "
            <h1>Réponse à votre offre</h1>
            <p>Bonjour {$client->getName()},</p>
            <p>Votre offre pour une chambre {$roomType->getName()} à l'hôtel {$hotel->getName()} a été {$statusText}.</p>
            <p><strong>Détails de la réservation :</strong></p>
            <ul>
                <li>Type de chambre : {$roomType->getName()}</li>
                <li>Hôtel : {$hotel->getName()}</li>
                <li>Date d'arrivée : {$negotiation->getStartDate()->format('d/m/Y')}</li>
                <li>Date de départ : {$negotiation->getEndDate()->format('d/m/Y')}</li>
                <li>Prix proposé : {$negotiation->getProposedPrice()} €</li>
            </ul>
        ";
        
        if ($negotiation->getStatus() === Negotiation::STATUS_ACCEPTED) {
            $content .= "
                <p>Vous pouvez maintenant procéder au paiement pour confirmer votre réservation.</p>
                <p>Vous avez 24 heures pour finaliser votre réservation.</p>
            ";
        } elseif ($negotiation->getStatus() === Negotiation::STATUS_COUNTER_OFFER) {
            $content .= "
                <p>L'hôtelier vous propose un prix de {$negotiation->getProposedPrice()} €.</p>
                <p>Vous avez 24 heures pour accepter cette contre-offre.</p>
            ";
        }
        
        $content .= "<p>Connectez-vous à votre compte MatchRoom pour plus de détails.</p>";
        
        $this->sendEmail($client->getEmail(), $subject, $content);
    }

    /**
     * Envoie une confirmation de réservation
     */
    public function sendBookingConfirmation(User $client, Negotiation $negotiation, string $invoicePdfPath): void
    {
        $roomType = $negotiation->getRoomType();
        $hotel = $roomType->getHotel();
        
        $subject = "Confirmation de votre réservation à {$hotel->getName()}";
        $content = "
            <h1>Réservation confirmée</h1>
            <p>Bonjour {$client->getName()},</p>
            <p>Votre réservation pour une chambre {$roomType->getName()} à l'hôtel {$hotel->getName()} est confirmée.</p>
            <p><strong>Détails de la réservation :</strong></p>
            <ul>
                <li>Type de chambre : {$roomType->getName()}</li>
                <li>Hôtel : {$hotel->getName()}</li>
                <li>Adresse : {$hotel->getAddress()}</li>
                <li>Date d'arrivée : {$negotiation->getStartDate()->format('d/m/Y')}</li>
                <li>Date de départ : {$negotiation->getEndDate()->format('d/m/Y')}</li>
                <li>Prix : {$negotiation->getProposedPrice()} €</li>
            </ul>
            <p>Vous trouverez en pièce jointe votre facture.</p>
            <p>Nous vous souhaitons un excellent séjour !</p>
        ";
        
        $email = (new Email())
            ->from($this->senderEmail)
            ->to($client->getEmail())
            ->subject($subject)
            ->html($content)
            ->attachFromPath($invoicePdfPath);
        
        $this->mailer->send($email);
    }

    /**
     * Méthode générique pour envoyer un email
     */
    private function sendEmail(string $to, string $subject, string $htmlContent): void
    {
        $email = (new Email())
            ->from($this->senderEmail)
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);
        
        $this->mailer->send($email);
    }
}
