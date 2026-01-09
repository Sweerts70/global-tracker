<?php

namespace App\Entity;

use App\Repository\TrackPointRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrackPointRepository::class)]
#[ORM\Table(name: 'track_points')]
class TrackPoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $addedOn = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 6)]
    private ?string $lat = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 6)]
    private ?string $lon = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $source = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddedOn(): ?\DateTime
    {
        return $this->addedOn;
    }

    public function setAddedOn(\DateTime $addedOn): static
    {
        $this->addedOn = $addedOn;

        return $this;
    }

    public function getLat(): ?string
    {
        return $this->lat;
    }

    public function setLat(string $lat): static
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLon(): ?string
    {
        return $this->lon;
    }

    public function setLon(string $lon): static
    {
        $this->lon = $lon;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }
}
