<?php

namespace App\Entity;

use App\Repository\VisitorLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VisitorLogRepository::class)]
class VisitorLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $visitedOn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ipTruncated = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ipHash = null;

    #[ORM\Column(length: 10)]
    private ?string $method = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $host = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $path = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $queryString = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $referer = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $acceptLanguage = null;

    #[ORM\Column(nullable: true)]
    private ?bool $dnt = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $countryIso = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $regionName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cityName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $timezone = null;

    #[ORM\Column(nullable: true)]
    private ?int $asn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $asOrg = null;

    public function __construct()
    {
        $this->visitedOn = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVisitedOn(): ?\DateTimeImmutable
    {
        return $this->visitedOn;
    }

    public function setVisitedOn(\DateTimeImmutable $visitedOn): static
    {
        $this->visitedOn = $visitedOn;

        return $this;
    }

    public function getIpTruncated(): ?string
    {
        return $this->ipTruncated;
    }

    public function setIpTruncated(?string $ipTruncated): static
    {
        $this->ipTruncated = $ipTruncated;

        return $this;
    }

    public function getIpHash(): ?string
    {
        return $this->ipHash;
    }

    public function setIpHash(?string $ipHash): static
    {
        $this->ipHash = $ipHash;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function setQueryString(?string $queryString): static
    {
        $this->queryString = $queryString;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }

    public function setReferer(?string $referer): static
    {
        $this->referer = $referer;

        return $this;
    }

    public function getAcceptLanguage(): ?string
    {
        return $this->acceptLanguage;
    }

    public function setAcceptLanguage(?string $acceptLanguage): static
    {
        $this->acceptLanguage = $acceptLanguage;

        return $this;
    }

    public function isDnt(): ?bool
    {
        return $this->dnt;
    }

    public function setDnt(?bool $dnt): static
    {
        $this->dnt = $dnt;

        return $this;
    }

    public function getCountryIso(): ?string
    {
        return $this->countryIso;
    }

    public function setCountryIso(?string $countryIso): static
    {
        $this->countryIso = $countryIso;

        return $this;
    }

    public function getRegionName(): ?string
    {
        return $this->regionName;
    }

    public function setRegionName(?string $regionName): static
    {
        $this->regionName = $regionName;

        return $this;
    }

    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    public function setCityName(?string $cityName): static
    {
        $this->cityName = $cityName;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getAsn(): ?int
    {
        return $this->asn;
    }

    public function setAsn(?int $asn): static
    {
        $this->asn = $asn;

        return $this;
    }

    public function getAsOrg(): ?string
    {
        return $this->asOrg;
    }

    public function setAsOrg(?string $asOrg): static
    {
        $this->asOrg = $asOrg;

        return $this;
    }
}
