<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Blog\Article\Application\Model\CreateArticleCommand;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Shared\ValueObject\ArticleBodyValueObject;
use App\Shared\ValueObject\ArticleTitleValueObject;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class CreateArticleHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private EventDispatcherInterface $eventDispatcher;
    private NormalizerInterface $serializer;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        EventDispatcherInterface $eventDispatcher,
        NormalizerInterface $serializer,
    ) {
        $this->articleRepository = $articleRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(CreateArticleCommand $createArticleCommand): string
    {
        $title = new ArticleTitleValueObject($createArticleCommand->getTitle());
        $body = new ArticleBodyValueObject($createArticleCommand->getBody());

        $article = Article::create(
            new ArticleId(Uuid::v4()->toString()),
            $title,
            $body,
            new CategoryId($createArticleCommand->getCategory())
        );

        $this->articleRepository->save($article);

        foreach ($article->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        /** @var array<string, mixed> $normalized */
        $normalized = $this->serializer->normalize($article);
        // A draft has no author yet.
        $normalized['authorName'] = null;

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
