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
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $is_system = null;

    #[ORM\Column(nullable: true)]
    private ?int $id_user = null;

    #[ORM\Column]
    private ?int $id_user_preferences = null;

    /**
     * @var Collection<int, UserPreference>
     */
    #[ORM\OneToMany(targetEntity: UserPreference::class, mappedBy: 'preferenceType')]
    private Collection $userPreferences;

    public function __construct()
    {
        $this->userPreferences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(?int $id_user): static
    {
        $this->id_user = $id_user;

        return $this;
    }

    public function getIdUserPreferences(): ?int
    {
        return $this->id_user_preferences;
    }

    public function setIdUserPreferences(int $id_user_preferences): static
    {
        $this->id_user_preferences = $id_user_preferences;

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
