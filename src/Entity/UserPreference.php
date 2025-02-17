<?php

namespace App\Entity;

use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPreferenceRepository::class)]
class UserPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $id_preference_type = null;

    #[ORM\Column(length: 50)]
    private ?string $choose_value = null;

    #[ORM\ManyToOne(inversedBy: 'userPreferences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userPreferences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PreferenceType $preferenceType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPreferenceType(): ?int
    {
        return $this->id_preference_type;
    }

    public function setIdPreferenceType(int $id_preference_type): static
    {
        $this->id_preference_type = $id_preference_type;

        return $this;
    }

    public function getChooseValue(): ?string
    {
        return $this->choose_value;
    }

    public function setChooseValue(string $choose_value): static
    {
        $this->choose_value = $choose_value;

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

    public function getPreferenceType(): ?PreferenceType
    {
        return $this->preferenceType;
    }

    public function setPreferenceType(?PreferenceType $preferenceType): static
    {
        $this->preferenceType = $preferenceType;

        return $this;
    }
}
