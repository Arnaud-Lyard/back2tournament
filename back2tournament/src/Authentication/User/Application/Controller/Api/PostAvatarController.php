<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Controller\Api;

use App\Authentication\User\Application\Model\ChangeAvatarCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user/me/avatar', name: 'api_user_avatar_post', methods: ['POST'])]
#[OA\Tag(name: 'User')]
#[OA\RequestBody(
    required: true,
    description: 'The image becomes the picture of the signed-in user, in place of the former one: it is cropped to a centred square of 256 pixels at most, stripped of its metadata and stored as WebP.',
    content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(ref: '#/components/schemas/ImageUpload')),
)]
#[OA\Response(response: 200, description: 'The signed-in user, as GET /api/user/me answers, with the new picture in `avatar`', content: new OA\JsonContent(ref: '#/components/schemas/CurrentUser'))]
#[OA\Response(response: 400, description: 'No image, or not a JPEG, PNG, WebP or GIF image of 8 MB and 40 megapixels at most', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
final class PostAvatarController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $image = $request->files->get('image');

        return JsonResponse::fromJsonString($this->handle(new ChangeAvatarCommand(
            $image instanceof UploadedFile && $image->isValid() ? $image->getContent() : '',
        )));
    }
}
