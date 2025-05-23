<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
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

    #[ORM\Column(name: "profil_picture", type: Types::TEXT, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column ]
    private ?int $credits = 0;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $rating = null;

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

    #[ORM\OneToMany(mappedBy: 'sender', targetEntity: Review::class)]
    private Collection $senderReviews;

    #[ORM\OneToMany(mappedBy: 'recipient', targetEntity: Review::class)]
    private Collection $recipientReviews;




    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->cars = new ArrayCollection();
        $this->userPreferences = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->carpools = new ArrayCollection();
        $this->carpoolParticipations = new ArrayCollection();
        $this->senderReviews = new ArrayCollection();
        $this->recipientReviews = new ArrayCollection();
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(int $id_user): self
    {
        $this->id_user = $id_user;
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
        return $this->profilePicture;
    }

    public function setProfilPicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

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

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): self
    {
        $this->rating = $rating;
        return $this;
    }


    public function getSenderReviews(): Collection
    {
        return $this->senderReviews;
    }

    public function addGivenReview(Review $review): static
    {
        if (!$this->senderReviews->contains($review)) {
            $this->senderReviews->add($review);
            $review->setSender($this);
        }
        return $this;
    }

    public function removeGivenReview(Review $review): static
    {
        if ($this->senderReviews->removeElement($review)) {
            if ($review->getSender() === $this) {
                $review->setSender(null);
            }
        }
        return $this;
    }


    public function getRecipientReviews(): Collection
    {
        return $this->recipientReviews;
    }

    public function addReceivedReview(Review $review): static
    {
        if (!$this->recipientReviews->contains($review)) {
            $this->recipientReviews->add($review);
            $review->setRecipient($this);
        }
        return $this;
    }

    public function removeReceivedReview(Review $review): static
    {
        if ($this->recipientReviews->removeElement($review)) {
            if ($review->getRecipient() === $this) {
                $review->setRecipient(null);
            }
        }
        return $this;
    }



    public function getRoles(): array
    {
        $userRoles = $this->roles->map(function($role) {
            $roleName = 'ROLE_' . strtoupper($this->removeAccents($role->getNameRole()));
            return $roleName;
        })->toArray();
        
        // Garantit que chaque utilisateur a au moins ROLE_USER
        $userRoles[] = 'ROLE_USER';
        
        return array_unique($userRoles);
    }

    private function removeAccents(string $string): string
    {
        $unwanted_array = array(
            'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e',
            'É'=>'E', 'È'=>'E', 'Ê'=>'E', 'Ë'=>'E'
        );
        return strtr($string, $unwanted_array);
    }
    

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        //stockez des données temporaires sensibles
    }

    /**
     * Retourne l'identifiant utilisé pour l'authentification
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }


    /**
     * @return Collection<int, Role>
     */
    /*
    public function getUserRoles(): Collection
    {
        return $this->roles;
    }*/
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

    public function hasRole(Role $role): bool
    {
        return $this->roles->contains($role);
    }

    public function hasRoleByName(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->getNameRole() === $roleName) {
                return true;
            }
        }
        return false;
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
