<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Controller\Api;

use App\Competition\Profile\Clan\Application\Model\CreateClanCommand;
use App\Shared\Infrastructure\Http\JsonBody;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/clans/', name: 'api_clan_post', methods: ['POST'])]
#[OA\Tag(name: 'Clan')]
#[OA\RequestBody(
    required: true,
    description: 'Founds a clan in a game. The founder is the player profile the authenticated user holds in that game: it becomes the leader and first member. A profile belongs to one clan at most.',
    content: new OA\JsonContent(
        required: ['game', 'name', 'tag'],
        properties: [
            new OA\Property(property: 'game', type: 'string', format: 'uuid', description: 'Game ID'),
            new OA\Property(property: 'name', type: 'string', maxLength: 50, example: 'Back to Tournament'),
            new OA\Property(property: 'tag', type: 'string', minLength: 2, maxLength: 5, example: 'B2T', description: '2 to 5 letters or digits, stored upper-cased, unique within the game'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Clan created',
    content: new OA\JsonContent(ref: '#/components/schemas/Clan'),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 404, description: 'The authenticated user holds no player profile in this game')]
#[OA\Response(response: 409, description: 'The founder already belongs to a clan, or the tag is taken in this game')]
final class PostClanController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode(
            $request->getContent(),
            true, 512,
            JSON_THROW_ON_ERROR
        );

        return JsonResponse::fromJsonString($this->handle(new CreateClanCommand(
            JsonBody::string($parameters, 'game'),
            JsonBody::string($parameters, 'name'),
            JsonBody::string($parameters, 'tag'),
        )));
    }
}
