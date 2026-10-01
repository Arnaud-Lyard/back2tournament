<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Controller\Api;

use App\Authentication\User\Application\Model\DeleteAccountCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/me/deletion', name: 'api_account_deletion_post', methods: ['POST'])]
#[OA\Tag(name: 'User')]
#[OA\RequestBody(
    required: true,
    description: 'The authenticated user deletes their own account, and confirms it with their password.',
    content: new OA\JsonContent(
        required: ['password'],
        properties: [
            new OA\Property(property: 'password', type: 'string', format: 'password', description: 'The password of the account'),
        ],
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Account deleted. Its email, username, password and picture are erased, and nobody signs in with it again: the JWT it held stops working. Its comments and its places in clans are gone, and so are its player profiles that never competed; the profiles that did stay for the record under an anonymous battletag (`Anonyme#1234`), with their results and ratings. The articles it published stay, without an author name.',
    content: new OA\JsonContent(
        required: ['deleted'],
        properties: [new OA\Property(property: 'deleted', type: 'boolean', example: true)],
        type: 'object',
    ),
)]
#[OA\Response(response: 400, ref: '#/components/responses/BadRequest')]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, description: 'The password does not match')]
#[OA\Response(response: 409, description: 'The account still holds something others depend on: a clan it leads, to dissolve first, or a tournament it organizes still open for registration, to start or cancel first')]
final class PostAccountDeletionController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return JsonResponse::fromJsonString($this->handle(new DeleteAccountCommand(
            (string) ($parameters['password'] ?? ''),
        )));
    }
}
