<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Blog\Article\Application\Model\UpdateArticleCommand;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Blog\Shared\Domain\Provider\AuthorProviderInterface;
use App\Blog\Shared\Domain\Provider\CategoryIdProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\ValueObject\ArticleBodyValueObject;
use App\Shared\ValueObject\ArticleTitleValueObject;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class UpdateArticleHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private CategoryIdProviderInterface $categoryIdProvider;
    private AuthorProviderInterface $authorProvider;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;
    private NormalizerInterface $serializer;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        CategoryIdProviderInterface $categoryIdProvider,
        AuthorProviderInterface $authorProvider,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
        NormalizerInterface $serializer,
    ) {
        $this->articleRepository = $articleRepository;
        $this->categoryIdProvider = $categoryIdProvider;
        $this->authorProvider = $authorProvider;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(UpdateArticleCommand $updateArticleCommand): string
    {
        $articleId = new ArticleId($updateArticleCommand->getArticleId());
        $title = null === $updateArticleCommand->getTitle() ? null : new ArticleTitleValueObject($updateArticleCommand->getTitle());
        $body = null === $updateArticleCommand->getBody() ? null : new ArticleBodyValueObject($updateArticleCommand->getBody());
        $titleEnGiven = $updateArticleCommand->getTitleEn();
        $bodyEnGiven = $updateArticleCommand->getBodyEn();
        $titleEn = self::isBlank($titleEnGiven) ? null : new ArticleTitleValueObject((string) $titleEnGiven);
        $bodyEn = self::isBlank($bodyEnGiven) ? null : new ArticleBodyValueObject((string) $bodyEnGiven);
        $translates = null !== $titleEnGiven || null !== $bodyEnGiven;

        if (!$this->currentUserProvider->isGranted('ROLE_EDITOR')) {
            throw new PermissionDeniedException('only an editor edits an article');
        }

        $article = $this->articleRepository->findOneBy(['id' => $articleId->getValue()]);
        if (!$article instanceof Article) {
            throw new NotFoundException('article not found');
        }

        $categorySlug = $updateArticleCommand->getCategorySlug();
        $categoryId = null === $categorySlug ? null : new CategoryId($this->categoryIdProvider->bySlug($categorySlug));

        if (!$translates || null !== $title || null !== $body || null !== $categoryId) {
            Article::update($article, $title, $body, $categoryId);
        }
        if ($translates) {
            Article::translate(
                $article,
                null === $titleEnGiven ? self::englishTitleOf($article) : $titleEn,
                null === $bodyEnGiven ? self::englishBodyOf($article) : $bodyEn,
            );
        }

        $this->articleRepository->save($article);

        foreach ($article->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        /** @var array<string, mixed> $normalized */
        $normalized = $this->serializer->normalize($article);
        $author = $article->getAuthor()?->getValue();
        $normalized['authorName'] = null === $author ? null : ($this->authorProvider->usernames([$author])[$author] ?? null);

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }

    private static function isBlank(?string $value): bool
    {
        return null === $value || '' === trim($value);
    }

    private static function englishTitleOf(Article $article): ?ArticleTitleValueObject
    {
        return null === $article->getTitleEn() ? null : new ArticleTitleValueObject($article->getTitleEn());
    }

    private static function englishBodyOf(Article $article): ?ArticleBodyValueObject
    {
        return null === $article->getBodyEn() ? null : new ArticleBodyValueObject($article->getBodyEn());
    }
}
