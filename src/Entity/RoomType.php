<?php

namespace App\Entity;

use App\Repository\RoomTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomTypeRepository::class)]
class RoomType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'roomTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hotel $hotel = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $capacity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $basePrice = null;

    #[ORM\Column]
    private ?bool $available = true;

    #[ORM\OneToMany(mappedBy: 'roomType', targetEntity: RoomPhoto::class, orphanRemoval: true)]
    private Collection $roomPhotos;

    #[ORM\OneToMany(mappedBy: 'roomType', targetEntity: Negotiation::class)]
    private Collection $negotiations;

    public function __construct()
    {
        $this->roomPhotos = new ArrayCollection();
        $this->negotiations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(?Hotel $hotel): static
    {
        $this->hotel = $hotel;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getBasePrice(): ?string
    {
        return $this->basePrice;
    }

    public function setBasePrice(string $basePrice): static
    {
        $this->basePrice = $basePrice;

        return $this;
    }

    public function isAvailable(): ?bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): static
    {
        $this->available = $available;

        return $this;
    }

    /**
     * @return Collection<int, RoomPhoto>
     */
    public function getRoomPhotos(): Collection
    {
        return $this->roomPhotos;
    }

    public function addRoomPhoto(RoomPhoto $roomPhoto): static
    {
        if (!$this->roomPhotos->contains($roomPhoto)) {
            $this->roomPhotos->add($roomPhoto);
            $roomPhoto->setRoomType($this);
        }

        return $this;
    }

    public function removeRoomPhoto(RoomPhoto $roomPhoto): static
    {
        if ($this->roomPhotos->removeElement($roomPhoto)) {
            if ($roomPhoto->getRoomType() === $this) {
                $roomPhoto->setRoomType(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Negotiation>
     */
    public function getNegotiations(): Collection
    {
        return $this->negotiations;
    }

    public function addNegotiation(Negotiation $negotiation): static
    {
        if (!$this->negotiations->contains($negotiation)) {
            $this->negotiations->add($negotiation);
            $negotiation->setRoomType($this);
        }

        return $this;
    }

    public function removeNegotiation(Negotiation $negotiation): static
    {
        if ($this->negotiations->removeElement($negotiation)) {
            if ($negotiation->getRoomType() === $this) {
                $negotiation->setRoomType(null);
            }
        }

        return $this;
    }
}
