<?php

namespace App\Entity;

use App\Repository\CarpoolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarpoolRepository::class)]
#[ORM\Table(name: 'carpools')]
class Carpool
{
    public const STATUS_WAITING = 'attente';
    public const STATUS_ACTIVE = 'actif';
    public const STATUS_COMPLETED = 'terminé';
    public const STATUS_CANCELED = 'annulé';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_carpool = null;

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
    #[ORM\JoinColumn(name: "id_user", referencedColumnName: "id_user", nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'carpoolParticipations')]
    #[ORM\JoinTable(name: 'carpool_users',
        joinColumns: [new ORM\JoinColumn(name: 'id_carpool', referencedColumnName: 'id_carpool')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user')]
    )]

    private Collection $passengers;

    public function __construct()
    {
        $this->passengers = new ArrayCollection();
    }

    
    public function getIdCarpool(): ?int
    {
        return $this->id_carpool;
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

    /**
     * Nombre de place encore disponible
     */
    public function getAvailablePlace(): int
    {
        return $this->nbr_places - $this->passengers->count();
    }

    /**
     * Vérifie si covoit à encore de la place
     */
    public function canAccomodate(int $numberPassagers): bool
    {
        return $this->getAvailablePlace() >= $numberPassagers;
    }

    /**
     * Vérifie si covoit est complet
     */
    public function isFull(): bool
    {
        return $this->getAvailablePlace() <= 0;
    }

    /**
     * Nombre total de personne dans coivoit(véhicule)
     */
    public function getTotalPeopleVehicule(): int
    {
        //conducteur + les passagers
        return 1 + $this->passengers->count();
    }

    /**
     * Vérifie si le covoiturage est en attente
     */
    public function isWaitingCarpool(): bool
    {
        return $this->statut === self::STATUS_WAITING;
    }

    /**
     * Vérifie si le covoiturage est actif
     */
    public function isActiveCarpool(): bool
    {
        return $this->statut === self::STATUS_ACTIVE;
    }

    /**
     * Vérifie si le covoiturage est terminé
     */
    public function isCompletedCarpool(): bool
    {
        return $this->statut === self::STATUS_COMPLETED;
    }

    /**
     * Vérifie si le covoiturage est annulé
     */
    public function isCanceledCarpool(): bool
    {
        return $this->statut === self::STATUS_CANCELED;
    }
}
