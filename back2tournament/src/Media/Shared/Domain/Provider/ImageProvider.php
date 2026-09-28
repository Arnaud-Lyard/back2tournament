<?php

declare(strict_types=1);

namespace App\Media\Shared\Domain\Provider;

use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Image\Domain\Service\ImageCompressorInterface;
use App\Media\Image\Domain\Service\ImageStorageInterface;
use App\Shared\ValueObject\UploadedImageValueObject;
use Symfony\Component\Uid\Uuid;

final class ImageProvider implements ImageProviderInterface
{
    private ImageCompressorInterface $imageCompressor;
    private ImageStorageInterface $imageStorage;
    private string $publicUrl;

    public function __construct(
        ImageCompressorInterface $imageCompressor,
        ImageStorageInterface $imageStorage,
        string $publicUrl,
    ) {
        $this->imageCompressor = $imageCompressor;
        $this->imageStorage = $imageStorage;
        $this->publicUrl = rtrim($publicUrl, '/');
    }

    public function store(UploadedImageValueObject $image, ImageKind $kind): string
    {
        $compressed = $this->imageCompressor->compress($image, $kind);

        $key = \sprintf('%s/%s.%s', $kind->value, Uuid::v4()->toRfc4122(), $compressed->getExtension());
        $this->imageStorage->put($key, $compressed->getContent(), $compressed->getType());

        return $key;
    }

    public function remove(?string $key): void
    {
        if (null !== $key) {
            $this->imageStorage->delete($key);
        }
    }

    public function url(?string $key): ?string
    {
        return null === $key ? null : $this->publicUrl.'/'.$key;
    }
}
