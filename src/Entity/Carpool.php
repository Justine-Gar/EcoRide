<?php

namespace App\Entity;

use App\Repository\CarpoolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarpoolRepository::class)]
class Carpool
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_start = null;

    #[ORM\Column(length: 50)]
    private ?string $location_start = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $hour_start = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_reach = null;

    #[ORM\Column(length: 50)]
    private ?string $location_reach = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $hour_reach = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?int $credits = null;

    #[ORM\Column]
    private ?int $nbr_places = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $lat_start = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $lng_start = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $lat_reach = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $lng_reach = null;

    #[ORM\ManyToOne(inversedBy: 'carpools')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'carpoolParticipations')]
    private Collection $passengers;

    public function __construct()
    {
        $this->passengers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->date_start;
    }

    public function setDateStart(\DateTimeInterface $date_start): static
    {
        $this->date_start = $date_start;

        return $this;
    }

    public function getLocationStart(): ?string
    {
        return $this->location_start;
    }

    public function setLocationStart(string $location_start): static
    {
        $this->location_start = $location_start;

        return $this;
    }

    public function getHourStart(): ?\DateTimeInterface
    {
        return $this->hour_start;
    }

    public function setHourStart(\DateTimeInterface $hour_start): static
    {
        $this->hour_start = $hour_start;

        return $this;
    }

    public function getDateReach(): ?\DateTimeInterface
    {
        return $this->date_reach;
    }

    public function setDateReach(\DateTimeInterface $date_reach): static
    {
        $this->date_reach = $date_reach;

        return $this;
    }

    public function getLocationReach(): ?string
    {
        return $this->location_reach;
    }

    public function setLocationReach(string $location_reach): static
    {
        $this->location_reach = $location_reach;

        return $this;
    }

    public function getHourReach(): ?\DateTimeInterface
    {
        return $this->hour_reach;
    }

    public function setHourReach(\DateTimeInterface $hour_reach): static
    {
        $this->hour_reach = $hour_reach;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function getNbrPlaces(): ?int
    {
        return $this->nbr_places;
    }

    public function setNbrPlaces(int $nbr_places): static
    {
        $this->nbr_places = $nbr_places;

        return $this;
    }

    public function getLatStart(): ?string
    {
        return $this->lat_start;
    }

    public function setLatStart(?string $lat_start): static
    {
        $this->lat_start = $lat_start;

        return $this;
    }

    public function getLngStart(): ?string
    {
        return $this->lng_start;
    }

    public function setLngStart(?string $lng_start): static
    {
        $this->lng_start = $lng_start;

        return $this;
    }

    public function getLatReach(): ?string
    {
        return $this->lat_reach;
    }

    public function setLatReach(?string $lat_reach): static
    {
        $this->lat_reach = $lat_reach;

        return $this;
    }

    public function getLngReach(): ?string
    {
        return $this->lng_reach;
    }

    public function setLngReach(?string $lng_reach): static
    {
        $this->lng_reach = $lng_reach;

        return $this;
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

    /**
     * @return Collection<int, User>
     */
    public function getPassengers(): Collection
    {
        return $this->passengers;
    }

    public function addPassenger(User $passenger): static
    {
        if (!$this->passengers->contains($passenger)) {
            $this->passengers->add($passenger);
        }

        return $this;
    }

    public function removePassenger(User $passenger): static
    {
        $this->passengers->removeElement($passenger);

        return $this;
    }
}
