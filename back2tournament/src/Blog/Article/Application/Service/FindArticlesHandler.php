<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\FindArticlesQuery;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Enum\ArticleStatus;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Shared\Domain\Provider\AuthorProviderInterface;
use App\Blog\Shared\Domain\Provider\CategoryIdProviderInterface;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindArticlesHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private CategoryIdProviderInterface $categoryIdProvider;
    private AuthorProviderInterface $authorProvider;
    private CurrentUserProviderInterface $currentUserProvider;
    private NormalizerInterface $serializer;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        CategoryIdProviderInterface $categoryIdProvider,
        AuthorProviderInterface $authorProvider,
        CurrentUserProviderInterface $currentUserProvider,
        NormalizerInterface $serializer,
    ) {
        $this->articleRepository = $articleRepository;
        $this->categoryIdProvider = $categoryIdProvider;
        $this->authorProvider = $authorProvider;
        $this->currentUserProvider = $currentUserProvider;
        $this->serializer = $serializer;
    }

    public function __invoke(FindArticlesQuery $findArticlesQuery): string
    {
        $status = match ($findArticlesQuery->getStatus()) {
            'published' => ArticleStatus::PUBLISHED,
            'draft' => ArticleStatus::DRAFT,
            'all' => null,
            default => throw new ValidationException('status must be published, draft or all'),
        };

        if (ArticleStatus::PUBLISHED !== $status && !$this->currentUserProvider->isGranted('ROLE_EDITOR')) {
            throw new PermissionDeniedException('only an editor reads the drafts');
        }

        $categorySlug = $findArticlesQuery->getCategorySlug();
        $categoryId = null === $categorySlug ? null : $this->categoryIdProvider->bySlug($categorySlug);
        $search = $findArticlesQuery->getSearch();
        $limit = $findArticlesQuery->getLimit();

        $articles = $this->articleRepository->findPage(
            $search,
            $categoryId,
            $status,
            $limit,
            $findArticlesQuery->getOffset(),
        );

        $authors = [];
        foreach ($articles as $article) {
            if (null !== $article->getAuthor()) {
                $authors[] = $article->getAuthor()->getValue();
            }
        }
        $usernames = $this->authorProvider->usernames($authors);

        $normalized = [];
        foreach ($articles as $article) {
            $normalized[] = $this->normalizeArticle($article, $usernames);
        }

        $total = $this->articleRepository->countPage($search, $categoryId, $status);

        return json_encode([
            'items' => $normalized,
            'total' => $total,
            'page' => $findArticlesQuery->getPage(),
            'limit' => $limit,
            'pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, string> $usernames
     *
     * @return array<string, mixed>
     */
    private function normalizeArticle(Article $article, array $usernames): array
    {
        /** @var array<string, mixed> $normalized */
        $normalized = $this->serializer->normalize($article);
        $normalized['authorName'] = null === $article->getAuthor() ? null : ($usernames[$article->getAuthor()->getValue()] ?? null);

        return $normalized;
    }
}
