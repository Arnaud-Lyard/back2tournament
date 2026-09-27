<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Controller\Api;

use App\Competition\Profile\Game\Application\Model\ChangeGameImageCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/games/{id}/image', name: 'api_game_image_post', methods: ['POST'])]
#[OA\Tag(name: 'Game')]
#[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Game ID', schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\RequestBody(
    required: true,
    description: 'Requires an administrator. The image becomes the picture of the game, in place of the former one: it is resized to 1200 pixels at most a side, stripped of its metadata and stored as WebP.',
    content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/ImageUpload')),
)]
#[OA\Response(response: 200, description: 'The game with its new picture in `image`', content: new OA\JsonContent(ref: '#/components/schemas/Game'))]
#[OA\Response(response: 400, description: 'No image, or not a JPEG, PNG, WebP or GIF image of 8 MB and 40 megapixels at most', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
#[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
#[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
final class PostGameImageController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $image = $request->files->get('image');

        return JsonResponse::fromJsonString($this->handle(new ChangeGameImageCommand(
            $id,
            $image instanceof UploadedFile && $image->isValid() ? $image->getContent() : '',
        )));
    }
}
