<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Blog\Article\Application\Model\FindArticleCommentsQuery;
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
final class FindArticleCommentsHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private CommentRepositoryInterface $commentRepository;
    private AuthorProviderInterface $authorProvider;
    private NormalizerInterface $serializer;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        CommentRepositoryInterface $commentRepository,
        AuthorProviderInterface $authorProvider,
        NormalizerInterface $serializer,
    ) {
        $this->articleRepository = $articleRepository;
        $this->commentRepository = $commentRepository;
        $this->authorProvider = $authorProvider;
        $this->serializer = $serializer;
    }

    public function __invoke(FindArticleCommentsQuery $findArticleCommentsQuery): string
    {
        $articleId = $findArticleCommentsQuery->getArticleId();

        $article = $this->articleRepository->findOneBy(['id' => $articleId]);
        if (!$article instanceof Article || ArticleStatus::PUBLISHED !== $article->getStatus()) {
            throw new NotFoundException('article not found');
        }

        /** @var list<Comment> $comments */
        $comments = $this->commentRepository->findBy(['articleId' => $articleId], ['createdAt' => 'ASC', 'id' => 'ASC']);

        $authors = [];
        foreach ($comments as $comment) {
            if (null !== $comment->getAuthor()) {
                $authors[] = $comment->getAuthor()->getValue();
            }
        }
        $usernames = $this->authorProvider->usernames($authors);

        $normalized = [];
        foreach ($comments as $comment) {
            /** @var array<string, mixed> $item */
            $item = $this->serializer->normalize($comment);
            $item['authorName'] = null === $comment->getAuthor() ? null : ($usernames[$comment->getAuthor()->getValue()] ?? null);
            $normalized[] = $item;
        }

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
