<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Controller\Api;

use App\Competition\Fight\Application\Model\ChangeFightStatusCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/fights/{id}/status', name: 'api_fight_status_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Fight')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Fight ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'Requires an administrator, who settles a dispute between the sides of a fight not settled yet. `finished` settles it on `scores`, whatever was declared: the fight is `arbitrated`, and the bracket and the rankings move on as after a confirmation. `pending` sets a declaration aside: both sides are back to pending, and one of them declares again.',
    content: new OA\JsonContent(
        required: ['status'],
        properties: [
            new OA\Property(property: 'status', type: 'string', enum: ['finished', 'pending']),
            new OA\Property(
                property: 'scores',
                type: 'object',
                description: 'With `finished`: the score of each side, keyed by competitor id. A tournament fight cannot end in a draw.',
                additionalProperties: new OA\AdditionalProperties(type: 'integer', minimum: 0),
                example: ['3f4c3a8e-0f55-4d3b-9a43-6b1f0e7d8c21' => 3, '9b2e71d4-5c8a-4f16-8e0b-2d7c4a9f1e35' => 1],
            ),
        ],
    ),
)]
#[OA\Response(response: 200, description: 'The fight in its new status', content: new OA\JsonContent(ref: '#/components/schemas/FightSummary'))]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
#[OA\Response(response: 409, description: 'The fight is already settled, or has no declaration to set aside', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class PatchFightStatusController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $parameters = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return JsonResponse::fromJsonString($this->handle(new ChangeFightStatusCommand(
            $id,
            $parameters['status'] ?? '',
            $parameters['scores'] ?? null,
        )));
    }
}
