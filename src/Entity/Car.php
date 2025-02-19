<?php

namespace App\Entity;

use App\Repository\CarRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarRepository::class)]
#[ORM\Table(name: 'cars')]
class Car
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_cars")]
    private ?int $id_cars = null;

    #[ORM\Column(length: 50)]
    private ?string $marque = null;

    #[ORM\Column(length: 50)]
    private ?string $modele = null;

    #[ORM\Column(length: 50)]
    private ?string $color = null;

    #[ORM\Column(length: 50)]
    private ?string $energie = null;

    #[ORM\Column]
    private ?int $nbr_places = null;

    #[ORM\Column(length: 50)]
    private ?string $license_plate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $first_registration = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'cars')]
    #[ORM\JoinColumn(name: "id_user", referencedColumnName: "id_user", nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id_cars;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(string $marque): static
    {
        $this->marque = $marque;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getEnergie(): ?string
    {
        return $this->energie;
    }

    public function setEnergie(string $energie): static
    {
        $this->energie = $energie;

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

    public function getLicensePlate(): ?string
    {
        return $this->license_plate;
    }

    public function setLicensePlate(string $license_plate): static
    {
        $this->license_plate = $license_plate;

        return $this;
    }

    public function getFirstRegistration(): ?\DateTimeInterface
    {
        return $this->first_registration;
    }

    public function setFirstRegistration(\DateTimeInterface $first_registration): static
    {
        $this->first_registration = $first_registration;

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
}
