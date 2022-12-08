<?php

namespace App\Service\UploaderBundle\Naming;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

final class EncodedFileNamer implements NamerInterface
{
    public function name(object $object, PropertyMapping $mapping): string
    {
        /** @var UploadedFile|ReplacingFile $file */
        $file             = $mapping->getFile($object);
        $originalFileName = new \SplFileInfo($file->getClientOriginalName());

        return sprintf('%s-encoded.%s', $originalFileName->getBasename('.'.$originalFileName->getExtension()), $originalFileName->getExtension());
    }
}
