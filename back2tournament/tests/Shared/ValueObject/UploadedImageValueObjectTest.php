<?php

declare(strict_types=1);

namespace App\Tests\Shared\ValueObject;

use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\UploadedImageValueObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UploadedImageValueObjectTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAYCAIAAAAUMWhjAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAJklEQVRIiWM8oaHBQEvARFPTRy0YtWDUglELRi0YtWDUglELqAYA1J4BSIDvLE0AAAAASUVORK5CYII=';

    private const GIF = 'R0lGODdhIAAYAIAAAMkoKAAAACwAAAAAIAAYAAACGoSPqcvtD6OctNqLs968+w+G4kiW5omm6lcAADs=';

    private const WEBP = 'UklGRkoAAABXRUJQVlA4ID4AAAAwAwCdASogABgAPm00lkekIyIhKAgAgA2JZQDMSoAAQFBQAP7vKUf43m81s4//7B3/6Dv/0Hf7Jtvb2AAAAA==';

    private const JPEG = '/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gMTAK/9sAQwBQNzxGPDJQRkFGWlVQX3jIgnhubnj1r7mRyP///////////////////////////////////////////////////9sAQwFVWlp4aXjrgoLr/////////////////////////////////////////////////////////////////////////8AAEQgAGAAgAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AjoooqDoCiiigAooooAKKKKAP/9k=';

    private const BMP = 'Qk05AAAAAAAAADYAAAAoAAAAAQAAAAEAAAABABgAAAAAAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==';

    private const TINY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAwAAAAMCAIAAADZF8uwAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADklEQVQYlWNgGAVDFQAAAbwAATN8mzYAAAAASUVORK5CYII=';

    private const HUGE_PNG = 'iVBORw0KGgoAAAANSUhEUgAAJxAAABOICAIAAACYNeAf';

    /** @return iterable<string, array{string, string}> */
    public static function acceptedImages(): iterable
    {
        yield 'a PNG' => [self::PNG, 'image/png'];
        yield 'a JPEG' => [self::JPEG, 'image/jpeg'];
        yield 'a GIF' => [self::GIF, 'image/gif'];
        yield 'a WebP' => [self::WEBP, 'image/webp'];
    }

    #[DataProvider('acceptedImages')]
    public function test_a_jpeg_png_webp_or_gif_is_read_with_its_type_and_its_size(string $base64, string $type): void
    {
        $image = new UploadedImageValueObject(base64_decode($base64, true));

        $this->assertSame($type, $image->getType());
        $this->assertSame([32, 24], [$image->getWidth(), $image->getHeight()]);
        $this->assertSame(base64_decode($base64, true), $image->getContent());
    }

    /** @return iterable<string, array{string, string}> */
    public static function refusedFiles(): iterable
    {
        yield 'no file' => ['', 'No image was received'];
        yield 'a few bytes' => ['GIF89a', 'is not a JPEG, PNG, WebP or GIF image'];
        yield 'some text' => ['This is not an image, only words that go on and on.', 'is not a JPEG, PNG, WebP or GIF image'];
        yield 'an SVG, which may carry scripts' => ['<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><script>alert(1)</script></svg>', 'is not a JPEG, PNG, WebP or GIF image'];
        yield 'a BMP' => [base64_decode(self::BMP, true), 'is not a JPEG, PNG, WebP or GIF image'];
        yield 'a PNG cut in its header' => [substr(base64_decode(self::PNG, true), 0, 20), 'cannot be read'];
        yield 'an image under 16 pixels a side' => [base64_decode(self::TINY_PNG, true), '16 pixels a side at least'];
        yield 'an image over 40 megapixels' => [base64_decode(self::HUGE_PNG, true), '40 megapixels at most'];
    }

    #[DataProvider('refusedFiles')]
    public function test_anything_else_is_refused(string $content, string $reason): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains($reason);

        new UploadedImageValueObject($content);
    }

    public function test_a_file_over_8_mb_is_refused_before_it_is_read(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageIsOrContains('more than 8 MB');

        new UploadedImageValueObject(base64_decode(self::PNG, true).str_repeat("\0", UploadedImageValueObject::MAX_BYTES));
    }

    public function test_the_type_comes_from_the_content_whatever_the_file_claims(): void
    {
        $this->assertSame('image/gif', new UploadedImageValueObject(base64_decode(self::GIF, true))->getType());
    }
}
