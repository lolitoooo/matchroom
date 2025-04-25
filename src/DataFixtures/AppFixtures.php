<?php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Hotel;
use App\Entity\RoomType;
use App\Entity\RoomPhoto;
use App\Entity\Negotiation;
use App\Entity\Booking;
use App\Entity\Payment;
use App\Entity\Notification;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // Hôtelier
        $hotelier = new User();
        $hotelier->setEmail('hotelier@matchroom.com');
        $hotelier->setRoles(['ROLE_HOTEL']);
        $hotelier->setFirstname('Julie');
        $hotelier->setName('Durand');
        $hotelier->setPassword($this->hasher->hashPassword($hotelier, 'password'));
        $manager->persist($hotelier);

        // Client
        $client = new User();
        $client->setEmail('client@matchroom.com');
        $client->setRoles(['ROLE_USER']);
        $client->setFirstname('Alex');
        $client->setName('Martin');
        $client->setPassword($this->hasher->hashPassword($client, 'password'));
        $manager->persist($client);

        // Hôtel
        $hotel = new Hotel();
        $hotel->setName('Hôtel Côte d\'Azur');
        $hotel->setAddress('12 Promenade des Anglais, Nice');
        $hotel->setLatitude('43.6955');
        $hotel->setLongitude('7.2653');
        $hotel->setDescription('Hôtel 4 étoiles avec vue mer');
        $hotel->setUser($hotelier);
        $hotel->setNegotiationRules([
            'autoAcceptThreshold' => 20,
            'autoRejectThreshold' => 50
        ]);
        $manager->persist($hotel);

        // Chambre 1
        $room1 = new RoomType();
        $room1->setName('Chambre Standard');
        $room1->setDescription('Confort simple et moderne');
        $room1->setBasePrice(150);
        $room1->setCapacity(2);
        $room1->setHotel($hotel);
        $manager->persist($room1);

        $photo1 = new RoomPhoto();
        $photo1->setRoomType($room1);
        $photo1->setUrl('https://source.unsplash.com/random/800x600?room');
        $photo1->setAlt('Photo chambre standard');
        $photo1->setDisplayOrder(1);
        $manager->persist($photo1);

        // Chambre 2
        $room2 = new RoomType();
        $room2->setName('Suite Luxe');
        $room2->setDescription('Grande suite avec balcon et vue mer');
        $room2->setBasePrice(300);
        $room2->setCapacity(4);
        $room2->setHotel($hotel);
        $manager->persist($room2);

        $photo2 = new RoomPhoto();
        $photo2->setRoomType($room2);
        $photo2->setUrl('https://source.unsplash.com/random/800x600?luxury-room');
        $photo2->setAlt('Photo suite luxe');
        $photo2->setDisplayOrder(1);
        $manager->persist($photo2);

        // Négociation acceptée
        $nego1 = new Negotiation();
        $nego1->setClient($client);
        $nego1->setRoomType($room1);
        $nego1->setStartDate(new \DateTime('+7 days'));
        $nego1->setEndDate(new \DateTime('+10 days'));
        $nego1->setProposedPrice('140');
        $nego1->setStatus(Negotiation::STATUS_ACCEPTED);
        $nego1->setHotelResponse('Offre acceptée automatiquement');
        $manager->persist($nego1);

        // Négociation en contre-offre
        $nego2 = new Negotiation();
        $nego2->setClient($client);
        $nego2->setRoomType($room2);
        $nego2->setStartDate(new \DateTime('+12 days'));
        $nego2->setEndDate(new \DateTime('+14 days'));
        $nego2->setProposedPrice('220');
        $nego2->setStatus(Negotiation::STATUS_COUNTER_OFFER);
        $nego2->setHotelResponse('Contre-offre proposée à 250€');
        $manager->persist($nego2);

        // Paiement
        $payment = new Payment();
        $payment->setStripeId('pi_test_123456789');
        $payment->setAmount('140');
        $payment->setStatus(Payment::STATUS_COMPLETED);
        $manager->persist($payment);

        // Réservation
        $booking = new Booking();
        $booking->setNegotiation($nego1);
        $booking->setPayment($payment);
        $booking->setStatus('confirmed');
        $manager->persist($booking);

        // Notifications
        foreach ([$client, $hotelier] as $user) {
            $notif = new Notification();
            $notif->setUser($user);
            $notif->setType(Notification::TYPE_EMAIL);
            $notif->setMessage("Bienvenue sur MatchRoom !");
            $notif->setIsRead(false);
            $manager->persist($notif);
        }

        $manager->flush();
    }
}
