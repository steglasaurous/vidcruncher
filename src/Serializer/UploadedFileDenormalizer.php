<?php
namespace App\Serializer;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class UploadedFileDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        return $data;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return $data instanceof UploadedFile;
    }
}