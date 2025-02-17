<?php

namespace App\Entity;

use App\Repository\PreferenceTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PreferenceTypeRepository::class)]
class PreferenceType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_preference_types")]
    private ?int $id_preference_types = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(name: "is_systeme")]
    private ?bool $is_system = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "id_user", referencedColumnName: "id_user", nullable: true)]
    private ?User $user = null;

    /**
     * @var Collection<int, UserPreference>
     */
    #[ORM\OneToMany(targetEntity: UserPreference::class, mappedBy: 'preferenceType')]
    private Collection $userPreferences;

    public function __construct()
    {
        $this->userPreferences = new ArrayCollection();
    }

    public function getIdPreferenceType(): ?int
    {
        return $this->id_preference_types;
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

    public function isSystem(): ?bool
    {
        return $this->is_system;
    }

    public function setSystem(bool $is_system): static
    {
        $this->is_system = $is_system;

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
            $userPreference->setPreferenceType($this);
        }

        return $this;
    }

    public function removeUserPreference(UserPreference $userPreference): static
    {
        if ($this->userPreferences->removeElement($userPreference)) {
            // set the owning side to null (unless already changed)
            if ($userPreference->getPreferenceType() === $this) {
                $userPreference->setPreferenceType(null);
            }
        }

        return $this;
    }
}
