<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\ChangeArticleStatusCommand;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Enum\ArticleStatus;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class ChangeArticleStatusHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;
    private NormalizerInterface $serializer;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
        NormalizerInterface $serializer,
    ) {
        $this->articleRepository = $articleRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(ChangeArticleStatusCommand $changeArticleStatusCommand): string
    {
        $articleId = new ArticleId($changeArticleStatusCommand->getArticleId());
        $status = ArticleStatus::tryFrom($changeArticleStatusCommand->getStatus());
        if (null === $status) {
            throw new ValidationException('status must be draft or published');
        }

        if (!$this->currentUserProvider->isGranted('ROLE_EDITOR')) {
            throw new PermissionDeniedException('only an editor publishes an article');
        }
        $user = $this->currentUserProvider->getUser();

        $article = $this->articleRepository->findOneBy(['id' => $articleId->getValue()]);
        if (!$article instanceof Article) {
            throw new NotFoundException('article not found');
        }

        // Whoever publishes the article becomes its author.
        if (ArticleStatus::PUBLISHED === $status) {
            Article::publish($article, new AuthorId((string) $user->getId()));
        } else {
            Article::unpublish($article);
        }

        $this->articleRepository->save($article);

        foreach ($article->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        /** @var array<string, mixed> $normalized */
        $normalized = $this->serializer->normalize($article);
        $normalized['authorName'] = ArticleStatus::PUBLISHED === $status ? $user->getUsername() : null;

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
