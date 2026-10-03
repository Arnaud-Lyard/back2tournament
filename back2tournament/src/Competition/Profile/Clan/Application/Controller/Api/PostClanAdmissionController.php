<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Controller\Api;

use App\Competition\Profile\Clan\Application\Model\AdmitClanMemberCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/clans/{id}/admissions', name: 'api_clan_admission_post', methods: ['POST'])]
#[OA\Tag(name: 'Clan')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Clan ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'Only the clan leader accepts a request to join, sent with `POST /api/user/clans/{id}/requests`.',
    content: new OA\JsonContent(
        required: ['player'],
        properties: [
            new OA\Property(property: 'player', type: 'string', format: 'uuid', description: 'Player profile that asked to join'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Request accepted: the player is an `active` member.',
    content: new OA\JsonContent(ref: '#/components/schemas/ClanMember'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller does not lead the clan', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 404, description: 'The clan does not exist, or the player did not ask to join it', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 409, description: 'The player already is a member of the clan or of another one, or was invited and accepts the invitation themselves', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class PostClanAdmissionController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        return JsonResponse::fromJsonString($this->handle(new AdmitClanMemberCommand(
            $id,
            $parameters['player'],
        )));
    }
}
