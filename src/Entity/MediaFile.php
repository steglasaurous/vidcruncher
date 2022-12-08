<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Repository\MediaFileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

enum MediaType: string
{
    case OriginalVideo =  'original_video';
    case VideoFragment = 'video_fragment';
    case OutputVideo   = 'output_video';
}

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(inputFormats: ['multipart' => ['multipart/form-data']]),
    ],
    normalizationContext: ['groups' => ['media_file:read']],
    denormalizationContext: ['groups' => ['media_file:write']]
)]
#[ApiResource(
    uriTemplate: '/media/{id}/files',
    operations: [new GetCollection()],
    uriVariables: [
        'id' => new Link(
            fromProperty: 'mediaFiles',
            fromClass: Media::class
        ),
    ]
)]
#[ORM\Entity(repositoryClass: MediaFileRepository::class)]
#[Vich\Uploadable]
class MediaFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['media_file:read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Media::class, inversedBy: 'mediaFiles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['media_file:read', 'media_file:write'])]
    private Media $media;

    #[ORM\Column(nullable: false)]
    #[Groups(['media_file:read', 'media_file:write'])]
    private MediaType $mediaType;

    #[ORM\Column(nullable: true)]
    #[Groups(['media_file:read'])]
    private ?string $mediaPath = null;

    #[Vich\UploadableField(mapping: 'media_file', fileNameProperty: 'mediaPath')]
    #[Groups(['media_file:write'])]
    private ?File $file = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getMedia(): Media
    {
        return $this->media;
    }

    public function setMedia(Media $media): self
    {
        $this->media = $media;

        return $this;
    }

    public function getMediaType(): MediaType
    {
        return $this->mediaType;
    }

    public function setMediaType(MediaType $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getMediaPath(): string
    {
        return $this->mediaPath;
    }

    public function setMediaPath(string $mediaPath): self
    {
        $this->mediaPath = $mediaPath;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;

        return $this;
    }
}
