<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Blog\Article\Application\Model\FindArticleQuery;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Article\Domain\Repository\CommentRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class ArticleFinderHandler
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

    public function __invoke(FindArticleQuery $findArticleQuery): string
    {
        $articleId = $findArticleQuery->getArticleId();

        $article = $this->articleRepository->findOneBy(['id' => $articleId]);
        if (!$article) {
            throw new NotFoundException('article not found');
        }
        $comments = $this->commentRepository->findBy(['articleId' => $articleId]);

        $commentsNormalized = [];
        foreach ($comments as $comment) {
            $commentsNormalized[] = $this->serializer->normalize($comment);
        }

        $results = \array_merge(
            $this->serializer->normalize($article),
            ['comments' => $commentsNormalized]
        );

        return json_encode($results, JSON_THROW_ON_ERROR);
    }
}
