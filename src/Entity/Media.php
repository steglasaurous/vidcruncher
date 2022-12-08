<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\MediaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

enum MediaStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Done       = 'done';
    case Failed     = 'failed';
}

#[ApiResource]
#[ORM\Entity(repositoryClass: MediaRepository::class)]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(options: ['default' => 'now()'])]
    private \DateTime $added;

    #[ORM\Column(nullable: true)]
    private \DateTime $start;

    #[ORM\Column(nullable: true)]
    private \DateTime $completed;

    #[ORM\Column]
    private MediaStatus $status = MediaStatus::Pending;

    #[ORM\Column(nullable: true)]
    private string $workerName;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'media')]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project;

    #[ORM\OneToMany(mappedBy: 'media', targetEntity: MediaFile::class)]
    private Collection $mediaFiles;

    public function __construct()
    {
        $this->mediaFiles = new ArrayCollection();
        $this->added      = new \DateTime();
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

    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    public function setAdded(\DateTime $added): self
    {
        $this->added = $added;

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

    public function getStatus(): MediaStatus
    {
        return $this->status;
    }

    public function setStatus(MediaStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getWorkerName(): string
    {
        return $this->workerName;
    }

    public function setWorkerName(string $workerName): self
    {
        $this->workerName = $workerName;

        return $this;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getMediaFiles(): Collection
    {
        return $this->mediaFiles;
    }

    public function setMediaFiles(Collection $mediaFiles): self
    {
        $this->mediaFiles = $mediaFiles;

        return $this;
    }

    public function addMediaFile(MediaFile $mediaFile): self
    {
        $this->mediaFiles->add($mediaFile);

        return $this;
    }
}
