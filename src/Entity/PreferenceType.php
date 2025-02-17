<?php

namespace App\Entity;

use App\Repository\PreferenceTypeRepository;
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
}
