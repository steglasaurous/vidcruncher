<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;

enum MediaType: string {
    case OriginalVideo =  'original_video';
    case VideoFragment = 'video_fragment';
    case OutputVideo = 'output_video';
}

#[ApiResource]
#[ApiResource(
    uriTemplate: '/media/{id}/files',
    operations: [new GetCollection()],
    uriVariables: [
        'id' => new Link(
            fromProperty: 'mediaFiles',
            fromClass: Media::class
        )
    ]
)]
#[ORM\Entity]
class MediaFile {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Media::class, inversedBy: 'mediaFiles')]
    private Media $media;

    #[ORM\Column]
    private MediaType $mediaType;

    #[ORM\Column]
    private string $mediaPath;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): MediaFile
    {
        $this->id = $id;
        return $this;
    }

    public function getMedia(): Media
    {
        return $this->media;
    }

    public function setMedia(Media $media): MediaFile
    {
        $this->media = $media;
        return $this;
    }

    public function getMediaType(): MediaType
    {
        return $this->mediaType;
    }

    public function setMediaType(MediaType $mediaType): MediaFile
    {
        $this->mediaType = $mediaType;
        return $this;
    }

    public function getMediaPath(): string
    {
        return $this->mediaPath;
    }

    public function setMediaPath(string $mediaPath): MediaFile
    {
        $this->mediaPath = $mediaPath;
        return $this;
    }
}