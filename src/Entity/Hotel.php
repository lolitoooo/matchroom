<?php

namespace App\Entity;

use App\Repository\HotelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HotelRepository::class)]
class Hotel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'hotels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;
    
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $amenities = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $negotiationRules = [
        'autoAcceptThreshold' => 20, // Acceptation automatique si réduction ≤ 20%
        'autoRejectThreshold' => 50, // Refus automatique si réduction ≥ 50%
        'responseTimeHours' => 3,    // Délai de réponse pour l'hôtelier (en heures)
        'clientResponseTimeHours' => 24, // Délai de réponse pour le client (en heures)
        'blockingPeriodHours' => 24  // Période de blocage en cas de refus (en heures)
    ];

    #[ORM\OneToMany(mappedBy: 'hotel', targetEntity: RoomType::class, orphanRemoval: true)]
    private Collection $roomTypes;

    public function __construct()
    {
        $this->roomTypes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }
    
    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAmenities(): array
    {
        return $this->amenities;
    }

    public function setAmenities(?array $amenities): static
    {
        $this->amenities = $amenities;

        return $this;
    }

    public function getNegotiationRules(): array
    {
        return $this->negotiationRules;
    }

    public function setNegotiationRules(?array $negotiationRules): static
    {
        $this->negotiationRules = $negotiationRules;

        return $this;
    }
    
    public function getAutoAcceptThreshold(): int
    {
        return $this->negotiationRules['autoAcceptThreshold'] ?? 20;
    }
    
    public function setAutoAcceptThreshold(int $threshold): static
    {
        $this->negotiationRules['autoAcceptThreshold'] = $threshold;
        return $this;
    }
    
    public function getAutoRejectThreshold(): int
    {
        return $this->negotiationRules['autoRejectThreshold'] ?? 50;
    }
    
    public function setAutoRejectThreshold(int $threshold): static
    {
        $this->negotiationRules['autoRejectThreshold'] = $threshold;
        return $this;
    }
    
    public function getResponseTimeHours(): int
    {
        return $this->negotiationRules['responseTimeHours'] ?? 3;
    }
    
    public function setResponseTimeHours(int $hours): static
    {
        $this->negotiationRules['responseTimeHours'] = $hours;
        return $this;
    }
    
    public function getClientResponseTimeHours(): int
    {
        return $this->negotiationRules['clientResponseTimeHours'] ?? 24;
    }
    
    public function setClientResponseTimeHours(int $hours): static
    {
        $this->negotiationRules['clientResponseTimeHours'] = $hours;
        return $this;
    }
    
    public function getBlockingPeriodHours(): int
    {
        return $this->negotiationRules['blockingPeriodHours'] ?? 24;
    }
    
    public function setBlockingPeriodHours(int $hours): static
    {
        $this->negotiationRules['blockingPeriodHours'] = $hours;
        return $this;
    }

    /**
     * @return Collection<int, RoomType>
     */
    public function getRoomTypes(): Collection
    {
        // Forcer l'initialisation de la collection si elle est en mode lazy loading
        if ($this->roomTypes instanceof \Doctrine\ORM\PersistentCollection && !$this->roomTypes->isInitialized()) {
            $this->roomTypes->initialize();
        }
        return $this->roomTypes;
    }

    public function addRoomType(RoomType $roomType): static
    {
        if (!$this->roomTypes->contains($roomType)) {
            $this->roomTypes->add($roomType);
            $roomType->setHotel($this);
        }

        return $this;
    }

    public function removeRoomType(RoomType $roomType): static
    {
        if ($this->roomTypes->removeElement($roomType)) {
            if ($roomType->getHotel() === $this) {
                $roomType->setHotel(null);
            }
        }

        return $this;
    }
}
