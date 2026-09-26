<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Service;

use App\Blog\Article\Application\Model\CreateArticleCommand;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class CreateArticleHandler
{
    private ArticleRepositoryInterface $articleRepository;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;
    private RequestStack $requestStack;

    public function __construct(
        ArticleRepositoryInterface $articleRepository,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        RequestStack $requestStack,
    ) {
        $this->articleRepository = $articleRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->requestStack = $requestStack;
    }

    public function __invoke(CreateArticleCommand $createArticleCommand): void
    {
        $article = Article::create(
            new ArticleId(Uuid::v4()->toString()),
            $createArticleCommand->getTitle(),
            $createArticleCommand->getBody(),
            new AuthorId($createArticleCommand->getAuthor()),
            new CategoryId($createArticleCommand->getCategory())
        );

        $this->articleRepository->save($article);

        $this->requestStack->getSession()->set(
            'last_article_created',
            $this->serializer->serialize($article, 'json')
        );

        foreach ($article->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }
    }
}
