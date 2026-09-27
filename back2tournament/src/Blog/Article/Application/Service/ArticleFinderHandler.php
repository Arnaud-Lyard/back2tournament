<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\FindArticleQuery;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\Comment;
use App\Blog\Article\Domain\Enum\ArticleStatus;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Article\Domain\Repository\CommentRepositoryInterface;
use App\Blog\Shared\Domain\Provider\AuthorProviderInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class ArticleFinderHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private CommentRepositoryInterface $commentRepository;
    private AuthorProviderInterface $authorProvider;
    private CurrentUserProviderInterface $currentUserProvider;
    private NormalizerInterface $serializer;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        CommentRepositoryInterface $commentRepository,
        AuthorProviderInterface $authorProvider,
        CurrentUserProviderInterface $currentUserProvider,
        NormalizerInterface $serializer,
    ) {
        $this->articleRepository = $articleRepository;
        $this->commentRepository = $commentRepository;
        $this->authorProvider = $authorProvider;
        $this->currentUserProvider = $currentUserProvider;
        $this->serializer = $serializer;
    }

    public function __invoke(FindArticleQuery $findArticleQuery): string
    {
        $articleId = $findArticleQuery->getArticleId();

        $article = $this->articleRepository->findOneBy(['id' => $articleId]);
        // A draft exists for the editors only.
        if (!$article instanceof Article
            || (ArticleStatus::PUBLISHED !== $article->getStatus() && !$this->currentUserProvider->isGranted('ROLE_EDITOR'))) {
            throw new NotFoundException('article not found');
        }

        /** @var list<Comment> $comments */
        $comments = $this->commentRepository->findBy(['articleId' => $articleId], ['createdAt' => 'ASC', 'id' => 'ASC']);

        $users = null === $article->getAuthor() ? [] : [$article->getAuthor()->getValue()];
        foreach ($comments as $comment) {
            if (null !== $comment->getAuthor()) {
                $users[] = $comment->getAuthor()->getValue();
            }
        }
        $usernames = $this->authorProvider->usernames($users);

        $commentsNormalized = [];
        foreach ($comments as $comment) {
            $commentsNormalized[] = $this->normalizeComment($comment, $usernames);
        }

        /** @var array<string, mixed> $normalized */
        $normalized = $this->serializer->normalize($article);
        $normalized['authorName'] = null === $article->getAuthor() ? null : ($usernames[$article->getAuthor()->getValue()] ?? null);
        $normalized['comments'] = $commentsNormalized;

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }

    /**
     * The comment, and the name of the user who wrote it.
     *
     * @param array<string, string> $usernames keyed by user id
     *
     * @return array<string, mixed>
     */
    private function normalizeComment(Comment $comment, array $usernames): array
    {
        /** @var array<string, mixed> $normalized */
        $normalized = $this->serializer->normalize($comment);
        $normalized['authorName'] = null === $comment->getAuthor() ? null : ($usernames[$comment->getAuthor()->getValue()] ?? null);

        return $normalized;
    }
}
