<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Blog\Article\Application\Model\FindArticleCommentsQuery;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Article\Domain\Repository\CommentRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindArticleCommentsHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private CommentRepositoryInterface $commentRepository;
    private NormalizerInterface $serializer;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        CommentRepositoryInterface $commentRepository,
        NormalizerInterface $serializer,
    ) {
        $this->articleRepository = $articleRepository;
        $this->commentRepository = $commentRepository;
        $this->serializer = $serializer;
    }

    public function __invoke(FindArticleCommentsQuery $findArticleCommentsQuery): string
    {
        $articleId = $findArticleCommentsQuery->getArticleId();

        if (!$this->articleRepository->findOneBy(['id' => $articleId])) {
            throw new NotFoundException('article not found');
        }

        $comments = $this->commentRepository->findBy(['articleId' => $articleId], ['createdAt' => 'ASC', 'id' => 'ASC']);

        $normalized = [];
        foreach ($comments as $comment) {
            $normalized[] = $this->serializer->normalize($comment);
        }

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
