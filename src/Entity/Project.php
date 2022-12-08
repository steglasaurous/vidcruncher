<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

enum ProjectStatus: string
{
    case Pending          = 'pending';
    case Splitting        = 'splitting';
    case Processing       = 'processing';
    case ReadyForAssembly = 'ready_for_assembly';
    case Assembling       = 'assembling';
    case Done             = 'done';
    case Failed           = 'failed';
}

#[ApiResource]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(nullable: true)]
    private \DateTime $start;

    #[ORM\Column(nullable: true)]
    private \DateTime $completed;

    #[ORM\Column]
    private ProjectStatus $status = ProjectStatus::Pending;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Profile $profile;

    /**
     * Original file - for recordings primarily so they don't get processed more than once.
     */
    #[ORM\Column(nullable: true)]
    private string $originFilePath;

    #[ORM\Column(nullable: true)]
    private string $outputFilename;

    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'project')]
    private Collection $media;

    public function __construct()
    {
        $this->media = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function setStart(\DateTime $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getCompleted(): \DateTime
    {
        return $this->completed;
    }

    public function setCompleted(\DateTime $completed): self
    {
        $this->completed = $completed;

        return $this;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function getOriginFilePath(): string
    {
        return $this->originFilePath;
    }

    public function setOriginFilePath(string $originFilePath): self
    {
        $this->originFilePath = $originFilePath;

        return $this;
    }

    public function getOutputFilename(): string
    {
        return $this->outputFilename;
    }

    public function setOutputFilename(string $outputFilename): self
    {
        $this->outputFilename = $outputFilename;

        return $this;
    }

    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function setMedia(Collection $media): self
    {
        $this->media = $media;

        return $this;
    }
}
