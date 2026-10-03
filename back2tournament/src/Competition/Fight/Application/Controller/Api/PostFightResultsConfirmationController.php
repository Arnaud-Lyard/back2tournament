<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Event\OnFightResultConfirmationEvent;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/api/user/fights/{id}/results/confirmation', name: 'api_fight_results_confirmation_post', methods: ['POST'])]
#[OA\Tag(name: 'Fight')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Fight ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Response(
    response: 200,
    description: 'The side that did not declare agrees with the scores exactly as they were declared — read them with `GET /api/user/fights/{id}` first. No body is read. Both sides are settled on their final `status`; in a tournament, the winner moves on in the bracket. A side that disagrees does not confirm, and settles it with an admin.',
    content: new OA\JsonContent(ref: '#/components/schemas/FightSummary'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller stands on neither side, or is the side that declared', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 404, description: 'The fight, or a result for one of its two sides, does not exist', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 409, description: 'Nothing was declared yet, or the fight is already settled', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class PostFightResultsConfirmationController extends AbstractController
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(string $id): JsonResponse
    {
        $event = $this->eventDispatcher->dispatch(new OnFightResultConfirmationEvent($id));

        return JsonResponse::fromJsonString($event->getConfirmedFight());
    }
}
