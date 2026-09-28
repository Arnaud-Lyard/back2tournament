<?php

declare(strict_types=1);

namespace App\Media\Image\Infrastructure\Serializer;

use App\Media\Image\Domain\Attribute\StoredImage;
use App\Media\Shared\Domain\Provider\ImageProviderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class StoredImageNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const NORMALIZING = 'stored_image_normalizing';

    private ImageProviderInterface $imageProvider;

    /** @var array<class-string, list<string>> */
    private array $properties = [];

    public function __construct(ImageProviderInterface $imageProvider)
    {
        $this->imageProvider = $imageProvider;
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::NORMALIZING] = spl_object_id($data);
        $normalized = $this->normalizer->normalize($data, $format, $context);

        if (\is_array($normalized)) {
            foreach ($this->storedImages($data::class) as $property) {
                if (\array_key_exists($property, $normalized)) {
                    $normalized[$property] = $this->imageProvider->url($normalized[$property]);
                }
            }
        }

        return $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return \is_object($data)
            && ($context[self::NORMALIZING] ?? null) !== spl_object_id($data)
            && [] !== $this->storedImages($data::class);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }

    /**
     * @param class-string $class
     *
     * @return list<string>
     */
    private function storedImages(string $class): array
    {
        if (!isset($this->properties[$class])) {
            $this->properties[$class] = [];
            foreach (new \ReflectionClass($class)->getProperties() as $property) {
                if ([] !== $property->getAttributes(StoredImage::class)) {
                    $this->properties[$class][] = $property->getName();
                }
            }
        }

        return $this->properties[$class];
    }
}
