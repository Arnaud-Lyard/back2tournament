<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Shared\Infrastructure\EventListener\DomainExceptionListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class DomainExceptionListenerTest extends TestCase
{
    private function handle(\Throwable $throwable): ExceptionEvent
    {
        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable
        );

        (new DomainExceptionListener())($event);

        return $event;
    }

    /** @return array<string, array{\Throwable, int}> */
    public static function domainExceptionProvider(): array
    {
        return [
            'validation' => [new ValidationException('bad input'), 400],
            'permission denied' => [new PermissionDeniedException('nope'), 403],
            'not found' => [new NotFoundException('article not found'), 404],
            'conflict' => [new ConflictException('email already used'), 409],
        ];
    }

    #[DataProvider('domainExceptionProvider')]
    public function test_maps_domain_exception_to_its_status(\Throwable $exception, int $expectedStatus): void
    {
        $response = $this->handle($exception)->getResponse();

        self::assertNotNull($response);
        self::assertSame($expectedStatus, $response->getStatusCode());
        self::assertSame(['error' => $exception->getMessage()], json_decode($response->getContent(), true));
    }

    #[DataProvider('domainExceptionProvider')]
    public function test_maps_domain_exception_wrapped_by_messenger(\Throwable $exception, int $expectedStatus): void
    {
        $wrapped = new HandlerFailedException(new Envelope(new \stdClass()), [$exception]);

        $response = $this->handle($wrapped)->getResponse();

        self::assertNotNull($response);
        self::assertSame($expectedStatus, $response->getStatusCode());
        self::assertSame(['error' => $exception->getMessage()], json_decode($response->getContent(), true));
    }

    public function test_leaves_a_plain_invalid_argument_exception_alone(): void
    {
        self::assertNull($this->handle(new \InvalidArgumentException('internal bug'))->getResponse());
    }

    public function test_leaves_unrelated_exceptions_alone(): void
    {
        self::assertNull($this->handle(new \RuntimeException('boom'))->getResponse());
        self::assertNull($this->handle(new \LogicException('boom'))->getResponse());
    }

    public function test_unwraps_a_messenger_wrapped_http_exception(): void
    {
        $wrapped = new HandlerFailedException(
            new Envelope(new \stdClass()),
            [new NotFoundHttpException('article not found')]
        );

        $response = $this->handle($wrapped)->getResponse();

        self::assertNotNull($response);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame(['error' => 'article not found'], json_decode($response->getContent(), true));
    }

    public function test_leaves_a_top_level_http_exception_to_symfony(): void
    {
        self::assertNull($this->handle(new NotFoundHttpException('No route found'))->getResponse());
    }

    public function test_survives_a_cyclic_previous_chain(): void
    {
        $first = new \RuntimeException('a');
        $second = new \RuntimeException('b', 0, $first);
        $property = new \ReflectionProperty(\Exception::class, 'previous');
        $property->setValue($first, $second);

        self::assertNull($this->handle($second)->getResponse());
    }
}
