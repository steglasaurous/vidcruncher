<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\MediaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

enum MediaStatus: string {
    case Pending = 'pending';
    case Processing = 'processing';
    case ReadyForAssembly = 'ready_for_assembly';
    case Done = 'done';
    case Failed = 'failed';
}

#[ApiResource]
#[ORM\Entity(repositoryClass: MediaRepository::class)]
class Media {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(nullable: true)]
    private \DateTime $start;

    #[ORM\Column(nullable: true)]
    private \DateTime $completed;

    #[ORM\Column]
    private MediaStatus $status = MediaStatus::Pending;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'media')]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project;

    #[ORM\OneToMany(mappedBy: 'media', targetEntity: MediaFile::class)]
    private Collection $mediaFiles;


    public function __construct() {
        $this->mediaFiles = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Media
    {
        $this->id = $id;
        return $this;
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function setStart(\DateTime $start): Media
    {
        $this->start = $start;
        return $this;
    }

    public function getCompleted(): \DateTime
    {
        return $this->completed;
    }

    public function setCompleted(\DateTime $completed): Media
    {
        $this->completed = $completed;
        return $this;
    }

    public function getStatus(): MediaStatus
    {
        return $this->status;
    }

    public function setStatus(MediaStatus $status): Media
    {
        $this->status = $status;
        return $this;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): Media
    {
        $this->profile = $profile;
        return $this;
    }

    public function getOriginFilename(): string
    {
        return $this->originFilename;
    }

    public function setOriginFilename(string $originFilename): Media
    {
        $this->originFilename = $originFilename;
        return $this;
    }

    public function getAssembledFilename(): string
    {
        return $this->assembledFilename;
    }

    public function setAssembledFilename(string $assembledFilename): Media
    {
        $this->assembledFilename = $assembledFilename;
        return $this;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): Media
    {
        $this->project = $project;
        return $this;
    }

    public function getMediaFiles(): Collection
    {
        return $this->mediaFiles;
    }

    public function setMediaFiles(Collection $mediaFiles): Media
    {
        $this->mediaFiles = $mediaFiles;
        return $this;
    }

    public function addMediaFile(MediaFile $mediaFile): Media
    {
        $this->mediaFiles->add($mediaFile);

        return $this;
    }
}