<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\ChangeArticleImageCommand;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Shared\Domain\Provider\AuthorProviderInterface;
use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Shared\Domain\Provider\ImageProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\ValueObject\UploadedImageValueObject;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class ChangeArticleImageHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private ImageProviderInterface $imageProvider;
    private AuthorProviderInterface $authorProvider;
    private EventDispatcherInterface $eventDispatcher;
    private NormalizerInterface $serializer;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        CurrentUserProviderInterface $currentUserProvider,
        ImageProviderInterface $imageProvider,
        AuthorProviderInterface $authorProvider,
        EventDispatcherInterface $eventDispatcher,
        NormalizerInterface $serializer,
    ) {
        $this->articleRepository = $articleRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->imageProvider = $imageProvider;
        $this->authorProvider = $authorProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(ChangeArticleImageCommand $changeArticleImageCommand): string
    {
        $articleId = new ArticleId($changeArticleImageCommand->getArticleId());
        $image = null === $changeArticleImageCommand->getImage() ? null : new UploadedImageValueObject($changeArticleImageCommand->getImage());

        if (!$this->currentUserProvider->isGranted('ROLE_EDITOR')) {
            throw new PermissionDeniedException('only an editor illustrates an article');
        }

        $article = $this->articleRepository->findOneBy(['id' => $articleId->getValue()]);
        if (!$article instanceof Article) {
            throw new NotFoundException('article not found');
        }

        $previous = $article->getImage();
        if (null !== $image || null !== $previous) {
            Article::illustrate($article, null === $image ? null : $this->imageProvider->store($image, ImageKind::ARTICLE));
            $this->articleRepository->save($article);

            foreach ($article->pullDomainEvents() as $domainEvent) {
                $this->eventDispatcher->dispatch($domainEvent);
            }

            $this->imageProvider->remove($previous);
        }

        /** @var array<string, mixed> $normalized */
        $normalized = $this->serializer->normalize($article);
        $author = $article->getAuthor()?->getValue();
        $normalized['authorName'] = null === $author ? null : ($this->authorProvider->usernames([$author])[$author] ?? null);

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
