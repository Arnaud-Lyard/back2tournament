<?php

declare(strict_types=1);

namespace App\Media\Image\Infrastructure\Storage;

use App\Media\Image\Domain\Service\ImageStorageInterface;
use Aws\S3\S3ClientInterface;

final class S3ImageStorage implements ImageStorageInterface
{
    private const CACHE_CONTROL = 'public, max-age=31536000, immutable';

    private S3ClientInterface $client;
    private string $bucket;

    public function __construct(S3ClientInterface $client, string $bucket)
    {
        $this->client = $client;
        $this->bucket = $bucket;
    }

    public function put(string $key, string $content, string $type): void
    {
        $this->client->execute($this->client->getCommand('PutObject', [
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $content,
            'ContentType' => $type,
            'CacheControl' => self::CACHE_CONTROL,
        ]));
    }

    public function delete(string $key): void
    {
        $this->client->execute($this->client->getCommand('DeleteObject', [
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]));
    }
}
