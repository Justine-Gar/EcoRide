<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: "carpool_analytics")]
class CarpoolAnalytics
{
  #[MongoDB\Id]
  private ?string $id = null;

  #[MongoDB\Field(type: "int")]
  private ?int $carpoolId = null;

  #[MongoDB\Field(type: "int")]
  private ?int $userId = null;

  #[MongoDB\Field(type: "string")]
  private ?string $action = null; //creater, completed, cancelled, strated

  #[MongoDB\Field(type: "string")]
  private ?string $status = null; //attente, actif, terminé, annulé

  #[MongoDB\Field(type: "int")]
  private int $credits = 0;

  #[MongoDB\Field(type: "int")]
  private int $commissionCredits  = 0; //commision plateforme + 4cdts

  #[MongoDB\Field(type: "int")]
  private int $passengerCount = 0;

  #[MongoDB\Field(type: "string")]
  private ?string $departLocation = null;

  #[MongoDB\Field(type: "string")]
  private ?string $arrivalLocation = null;

  #[MongoDB\Field(type: "date")]
  private ?\DateTime $carpoolDate = null; //Date prévue du covoiturage

  #[MongoDB\Field(type: "date")]
  private ?\DateTime $createdAt = null;

  #[MongoDB\Field(type: "date")]
  private ?\DateTime $updatedAt = null;

  public function __construct()
  {
    $this->createdAt = new \DateTime();
    $this->updatedAt = new \DateTime();
  }

  public function getId(): ?string
  {
    return $this->id;
  }



  public function getCarpoolId(): ?int
  {
    return $this->carpoolId;
  }

  public function setCarpoolId(?int $carpoolId): self
  {
    $this->carpoolId = $carpoolId;
    return $this;
  }



  public function getUserId(): ?int
  {
    return $this->userId;
  }

  public function setUserId(?int $userId): self
  {
    $this->userId = $userId;
    return $this;
  }



  public function getAction(): ?string
  {
    return $this->action;
  }

  public function setAction(?string $action): self
  {
    $this->action = $action;
    $this->updatedAt = new \DateTime();
    return $this;
  }



  public function getStatus(): ?string
  {
    return $this->status;
  }

  public function setStatus(?string $status): self
  {
    $this->status = $status;
    $this->updatedAt = new \DateTime();
    return $this;
  }



  public function getCredits(): int
  {
    return $this->credits;
  }

  public function setCredits(int $credits): self
  {
    $this->credits = $credits;
    return $this;
  }



  public function getCommissionCredits(): int
  {
    return $this->commissionCredits;
  }

  public function setCommissionCredits(int $commissionCredits): self
  {
    $this->commissionCredits = $commissionCredits;
    return $this;
  }



  public function getPassengerCount(): int
  {
    return $this->passengerCount;
  }

  public function setPassengerCount(int $passengerCount): self
  {
    $this->passengerCount = $passengerCount;
    return $this;
  }



  public function getDepartLocation(): ?string
  {
    return $this->departLocation;
  }

  public function setDepartLocation(?string $departLocation): self
  {
    $this->departLocation = $departLocation;
    return $this;
  }



  public function getArrivalLocation(): ?string
  {
    return $this->arrivalLocation;
  }

  public function setArrivalLocation(?string $arrivalLocation): self
  {
    $this->arrivalLocation = $arrivalLocation;
    return $this;
  }



  public function getCarpoolDate(): ?\DateTime
  {
    return $this->carpoolDate;
  }

  public function setCarpoolDate(?\DateTime $carpoolDate): self
  {
    $this->carpoolDate = $carpoolDate;
    return $this;
  }



  public function getCreatedAt(): ?\DateTime
  {
    return $this->createdAt;
  }

  public function setCreatedAt(?\DateTime $createdAt): self
  {
    $this->createdAt = $createdAt;
    return $this;
  }



  public function getUpdatedAt(): ?\DateTime
  {
    return $this->updatedAt;
  }

  public function setUpdatedAt(?\DateTime $updatedAt): self
  {
    $this->updatedAt = $updatedAt;
    return $this;
  }
}