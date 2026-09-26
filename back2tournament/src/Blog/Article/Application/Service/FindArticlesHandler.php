<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Blog\Article\Application\Model\FindArticlesQuery;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Shared\Domain\Provider\CategoryIdProviderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindArticlesHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private CategoryIdProviderInterface $categoryIdProvider;
    private NormalizerInterface $serializer;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        CategoryIdProviderInterface $categoryIdProvider,
        NormalizerInterface $serializer,
    ) {
        $this->articleRepository = $articleRepository;
        $this->categoryIdProvider = $categoryIdProvider;
        $this->serializer = $serializer;
    }

    public function __invoke(FindArticlesQuery $findArticlesQuery): string
    {
        $categorySlug = $findArticlesQuery->getCategorySlug();
        $categoryId = null === $categorySlug ? null : $this->categoryIdProvider->bySlug($categorySlug);

        $search = $findArticlesQuery->getSearch();
        $limit = $findArticlesQuery->getLimit();

        $articles = $this->articleRepository->findPage(
            $search,
            $categoryId,
            $limit,
            $findArticlesQuery->getOffset(),
        );

        $normalized = [];
        foreach ($articles as $article) {
            $normalized[] = $this->serializer->normalize($article);
        }

        $total = $this->articleRepository->countPage($search, $categoryId);

        return json_encode([
            'items' => $normalized,
            'total' => $total,
            'page' => $findArticlesQuery->getPage(),
            'limit' => $limit,
            'pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
        ], JSON_THROW_ON_ERROR);
    }
}
