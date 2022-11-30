<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProfileRepository;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ApiResource]
class Profile {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(nullable: false)]
    private int $preset = 8;

    #[ORM\Column(nullable: false)]
    private int $crf = 40;

    #[ORM\Column]
    private string $inputPath;

    #[ORM\Column]
    private string $outputPath;

    #[ORM\Column]
    private bool $isLiveRecordings = false;

    #[ORM\Column]
    private int $processModifiedOlderThan;

    #[ORM\Column]
    private int $assembleAfterTime;

    #[ORM\Column]
    private bool $isActive;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Profile
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Profile
    {
        $this->name = $name;
        return $this;
    }

    public function getPreset(): int
    {
        return $this->preset;
    }

    public function setPreset(int $preset): Profile
    {
        $this->preset = $preset;
        return $this;
    }

    public function getCrf(): int
    {
        return $this->crf;
    }

    public function setCrf(int $crf): Profile
    {
        $this->crf = $crf;
        return $this;
    }

    public function getInputPath(): string
    {
        return $this->inputPath;
    }

    public function setInputPath(string $inputPath): Profile
    {
        $this->inputPath = $inputPath;
        return $this;
    }

    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    public function setOutputPath(string $outputPath): Profile
    {
        $this->outputPath = $outputPath;
        return $this;
    }

    public function isLiveRecordings(): bool
    {
        return $this->isLiveRecordings;
    }

    public function setIsLiveRecordings(bool $isLiveRecordings): Profile
    {
        $this->isLiveRecordings = $isLiveRecordings;
        return $this;
    }

    public function getProcessModifiedOlderThan(): int
    {
        return $this->processModifiedOlderThan;
    }

    public function setProcessModifiedOlderThan(int $processModifiedOlderThan): Profile
    {
        $this->processModifiedOlderThan = $processModifiedOlderThan;
        return $this;
    }

    public function getAssembleAfterTime(): int
    {
        return $this->assembleAfterTime;
    }

    public function setAssembleAfterTime(int $assembleAfterTime): Profile
    {
        $this->assembleAfterTime = $assembleAfterTime;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): Profile
    {
        $this->isActive = $isActive;
        return $this;
    }
}
