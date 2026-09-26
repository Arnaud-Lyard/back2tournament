<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Event\OnFightResultConfirmationEvent;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;;

#[Route('/api/fights/results/confirmation', name: 'api_fight_results_confirmation_post', methods: ['POST'])]
#[OA\Tag(name: 'Fight')]
#[OA\RequestBody(
    required: true,
    description: 'Names the fight to settle. Confirming means agreeing with the results exactly as they were declared, so read them with `GET /api/fights/{id}/results/{gameid}` first. A side that disagrees does not confirm, and settles it with an admin.',
    content: new OA\JsonContent(
        required: ['fight', 'game'],
        properties: [
            new OA\Property(property: 'fight', type: 'string', format: 'uuid', description: 'Fight whose declared results are being confirmed'),
            new OA\Property(property: 'game', type: 'string', format: 'uuid', description: 'Game of the fight: with the authenticated user it names the competitor confirming the results'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Both results are settled, each carrying its final `status` and `score`. The fight is returned, with `declaredBy` still naming the side that had declared.',
    content: new OA\JsonContent(ref: '#/components/schemas/Fight'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, description: 'The fight, or a result for one of its two sides, does not exist')]
final class PostFightResultsConfirmationController extends AbstractController
{
    use HandleTrait;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(MessageBusInterface $messageBus, EventDispatcherInterface $eventDispatcher)
    {
        $this->messageBus = $messageBus;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        $this->eventDispatcher->dispatch(new OnFightResultConfirmationEvent(
            $parameters['game'],
            $parameters['fight'],
        ));

        return JsonResponse::fromJsonString(
            $request->getSession()->get('last_fight_result_confirmed')
        );
    }
}
