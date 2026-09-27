<?php

declare(strict_types=1);

namespace App\Tests\Media\Image\Infrastructure;

use App\Media\Image\Infrastructure\Storage\S3ImageStorage;
use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use PHPUnit\Framework\TestCase;

final class S3ImageStorageTest extends TestCase
{
    private MockHandler $s3;

    protected function setUp(): void
    {
        $this->s3 = new MockHandler();
        $this->s3->append(new Result([]));
    }

    public function test_an_image_is_put_in_the_bucket_with_its_type_and_cached_for_good(): void
    {
        $this->storage()->put('articles/cover.webp', 'webp bytes', 'image/webp');

        $command = $this->lastCommand();
        $this->assertSame('PutObject', $command->getName());
        $this->assertSame(
            ['back2tournament', 'articles/cover.webp', 'webp bytes', 'image/webp', 'public, max-age=31536000, immutable'],
            [$command['Bucket'], $command['Key'], $command['Body'], $command['ContentType'], $command['CacheControl']],
        );
    }

    public function test_an_image_is_deleted_from_the_bucket(): void
    {
        $this->storage()->delete('avatars/old.webp');

        $command = $this->lastCommand();
        $this->assertSame(['DeleteObject', 'back2tournament', 'avatars/old.webp'], [$command->getName(), $command['Bucket'], $command['Key']]);
    }

    private function storage(): S3ImageStorage
    {
        return new S3ImageStorage(new S3Client([
            'version' => '2006-03-01',
            'region' => 'garage',
            'endpoint' => 'http://garage:3900',
            'use_path_style_endpoint' => true,
            'credentials' => ['key' => 'GK0123456789abcdef01234567', 'secret' => 'secret'],
            'handler' => $this->s3,
        ]), 'back2tournament');
    }

    private function lastCommand(): CommandInterface
    {
        return $this->s3->getLastCommand();
    }
}
