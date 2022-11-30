<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

// FIXME: Might make sense to move this to its own file
enum ProjectStatus: string {
    case Pending = 'pending';
    case Splitting = 'splitting';
    case Processing = 'processing';
    case ReadyForAssembly = 'ready_for_assembly';
    case Assembing = 'assembing';
    case Done = 'done';
    case Failed = 'failed';
}


#[ApiResource]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project {
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

    #[ORM\Column]
    private string $originFilePath;

    #[ORM\Column(nullable: true)]
    private string $assembledFilePath;

    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'project')]
    private Collection $media;

    public function __construct() {
        $this->media = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Project
    {
        $this->id = $id;
        return $this;
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function setStart(\DateTime $start): Project
    {
        $this->start = $start;
        return $this;
    }

    public function getCompleted(): \DateTime
    {
        return $this->completed;
    }

    public function setCompleted(\DateTime $completed): Project
    {
        $this->completed = $completed;
        return $this;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectStatus $status): Project
    {
        $this->status = $status;
        return $this;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): Project
    {
        $this->profile = $profile;
        return $this;
    }

    public function getOriginFilePath(): string
    {
        return $this->originFilePath;
    }

    public function setOriginFilePath(string $originFilePath): Project
    {
        $this->originFilePath = $originFilePath;
        return $this;
    }

    public function getAssembledFilePath(): string
    {
        return $this->assembledFilePath;
    }

    public function setAssembledFilePath(string $assembledFilePath): Project
    {
        $this->assembledFilePath = $assembledFilePath;
        return $this;
    }

    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function setMedia(Collection $media): Project
    {
        $this->media = $media;
        return $this;
    }
}