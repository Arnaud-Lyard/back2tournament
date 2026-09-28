<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\CreateCommentCommand;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Entity\CommentId;
use App\Blog\Article\Domain\Enum\ArticleStatus;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Article\Domain\Repository\CommentRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class CreateCommentHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private CommentRepositoryInterface $commentRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;
    private NormalizerInterface $serializer;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        CommentRepositoryInterface $commentRepository,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
        NormalizerInterface $serializer
    ) {
        $this->articleRepository = $articleRepository;
        $this->commentRepository = $commentRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(CreateCommentCommand $createCommentCommand): string
    {
        $user = $this->currentUserProvider->getUser();

        $article = $this->articleRepository->findOneBy(['id' => $createCommentCommand->getArticleId()]);
        if (!$article instanceof Article
            || (ArticleStatus::PUBLISHED !== $article->getStatus() && !$this->currentUserProvider->isGranted('ROLE_EDITOR'))) {
            throw new NotFoundException('article not found');
        }

        $comment = Article::createComment(
            $article,
            new CommentId(Uuid::v4()->toString()),
            $createCommentCommand->getMessage(),
            new AuthorId((string) $user->getId()),
        );

        $this->commentRepository->save($comment);

        foreach ($article->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        /** @var array<string, mixed> $normalized */
        $normalized = $this->serializer->normalize($comment);
        $normalized['authorName'] = $user->getUsername();

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
