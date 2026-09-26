<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Shared\Exception\DomainExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Turns business failures into the HTTP status they mean, for every controller.
 *
 */
#[AsEventListener(event: ExceptionEvent::class)]
final class DomainExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $depth = 0;

        foreach ($this->causeChain($event->getThrowable()) as $cause) {
            if ($cause instanceof DomainExceptionInterface) {
                $event->setResponse($this->toJson($cause->getMessage(), $cause->getStatusCode()));

                return;
            }

            if ($depth > 0 && $cause instanceof HttpExceptionInterface) {
                $event->setResponse($this->toJson($cause->getMessage(), $cause->getStatusCode()));

                return;
            }

            ++$depth;
        }
    }

    private function toJson(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }

    /**
     * @return iterable<\Throwable>
     */
    private function causeChain(\Throwable $throwable): iterable
    {
        $seen = [];

        for ($current = $throwable; null !== $current; $current = $current->getPrevious()) {
            if (isset($seen[spl_object_id($current)])) {
                return;
            }
            $seen[spl_object_id($current)] = true;

            yield $current;
        }
    }
}
