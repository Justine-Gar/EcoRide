<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_user")]
    private ?int $id_user = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone_number = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $profil_picture = null;


    /**
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles',
        joinColumns: [new ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'id_role', referencedColumnName: 'id_role')]
    )]
    private Collection $roles;

    /**
     * @var Collection<int, Car>
     */
    #[ORM\OneToMany(targetEntity: Car::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $cars;

    /**
     * @var Collection<int, UserPreference>
     */
    #[ORM\OneToMany(targetEntity: UserPreference::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userPreferences;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $reviews;

    /**
     * @var Collection<int, Carpool>
     */
    #[ORM\OneToMany(targetEntity: Carpool::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $carpools;

    /**
     * @var Collection<int, Carpool>
     */
    #[ORM\ManyToMany(targetEntity: Carpool::class, mappedBy: 'passengers')]
    private Collection $carpoolParticipations;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->cars = new ArrayCollection();
        $this->userPreferences = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->carpools = new ArrayCollection();
        $this->carpoolParticipations = new ArrayCollection();
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): static
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getProfilPicture(): ?string
    {
        return $this->profil_picture;
    }

    public function setProfilPicture(?string $profil_picture): static
    {
        $this->profil_picture = $profil_picture;

        return $this;
    }


    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        $this->roles->removeElement($role);

        return $this;
    }

    /**
     * @return Collection<int, Car>
     */
    public function getCars(): Collection
    {
        return $this->cars;
    }

    public function addCar(Car $car): static
    {
        if (!$this->cars->contains($car)) {
            $this->cars->add($car);
            $car->setUser($this);
        }

        return $this;
    }

    public function removeCar(Car $car): static
    {
        if ($this->cars->removeElement($car)) {
            // set the owning side to null (unless already changed)
            if ($car->getUser() === $this) {
                $car->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserPreference>
     */
    public function getUserPreferences(): Collection
    {
        return $this->userPreferences;
    }

    public function addUserPreference(UserPreference $userPreference): static
    {
        if (!$this->userPreferences->contains($userPreference)) {
            $this->userPreferences->add($userPreference);
            $userPreference->setUser($this);
        }

        return $this;
    }

    public function removeUserPreference(UserPreference $userPreference): static
    {
        if ($this->userPreferences->removeElement($userPreference)) {
            // set the owning side to null (unless already changed)
            if ($userPreference->getUser() === $this) {
                $userPreference->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReview(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setUser($this);
        }

        return $this;
    }

    public function removereview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getUser() === $this) {
                $review->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Carpool>
     */
    public function getCarpools(): Collection
    {
        return $this->carpools;
    }

    public function addCarpool(Carpool $carpool): static
    {
        if (!$this->carpools->contains($carpool)) {
            $this->carpools->add($carpool);
            $carpool->setUser($this);
        }

        return $this;
    }

    public function removeCarpool(Carpool $carpool): static
    {
        if ($this->carpools->removeElement($carpool)) {
            // set the owning side to null (unless already changed)
            if ($carpool->getUser() === $this) {
                $carpool->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Carpool>
     */
    public function getCarpoolParticipations(): Collection
    {
        return $this->carpoolParticipations;
    }

    public function addCarpoolParticipation(Carpool $carpoolParticipation): static
    {
        if (!$this->carpoolParticipations->contains($carpoolParticipation)) {
            $this->carpoolParticipations->add($carpoolParticipation);
            $carpoolParticipation->addPassenger($this);
        }

        return $this;
    }

    public function removeCarpoolParticipation(Carpool $carpoolParticipation): static
    {
        if ($this->carpoolParticipations->removeElement($carpoolParticipation)) {
            $carpoolParticipation->removePassenger($this);
        }

        return $this;
    }
}
