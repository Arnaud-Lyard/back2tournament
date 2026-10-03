<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Controller\Api;

use App\Competition\Profile\Clan\Application\Model\DissolveClanCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/clans/{id}/dissolution', name: 'api_clan_dissolution_post', methods: ['POST'])]
#[OA\Tag(name: 'Clan')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Clan ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'Only the clan leader dissolves the clan, and confirms it with their password.',
    content: new OA\JsonContent(
        required: ['password'],
        properties: [
            new OA\Property(property: 'password', type: 'string', format: 'password', description: 'The password of the leader'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Clan dissolved, as it stands now: `dissolvedAt` is set. Its members, invitations and requests to join are gone, and so are its teams that never competed; the teams that did stay for the record, and enter no new fight or tournament. It leaves the clan lists and the rankings, and its tag may be founded again. The fights it played keep it, marked dissolved.',
    content: new OA\JsonContent(ref: '#/components/schemas/Clan'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The caller does not lead the clan, or the password does not match', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 404, description: 'The clan does not exist, or is already dissolved', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class PostClanDissolutionController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $parameters = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return JsonResponse::fromJsonString($this->handle(new DissolveClanCommand(
            $id,
            (string) ($parameters['password'] ?? ''),
        )));
    }
}
