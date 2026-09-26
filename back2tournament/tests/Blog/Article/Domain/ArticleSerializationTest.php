<?php

declare(strict_types=1);

namespace App\Tests\Blog\Article\Domain;

use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ArticleSerializationTest extends KernelTestCase
{
    private const ARTICLE_ID = '11111111-1111-4111-8111-111111111111';
    private const AUTHOR_ID = '22222222-2222-4222-8222-222222222222';
    private const CATEGORY_ID = '33333333-3333-4333-8333-333333333333';

    public function test_exposes_exactly_the_fields_the_article_schema_documents(): void
    {
        self::assertSame(
            ['category', 'id', 'createdAt', 'updatedAt', 'body', 'title', 'author'],
            array_keys($this->normalized($this->article()))
        );
    }

    public function test_identifiers_are_serialized_as_value_objects(): void
    {
        $payload = $this->normalized($this->article());

        self::assertSame(['value' => self::ARTICLE_ID], $payload['id']);
        self::assertSame(['value' => self::AUTHOR_ID], $payload['author']);
        self::assertSame(['value' => self::CATEGORY_ID], $payload['category']);
    }

    public function test_dates_are_serialized_as_rfc3339_strings(): void
    {
        $article = $this->article();
        $payload = $this->normalized($article);

        self::assertSame($article->getCreatedAt()?->format(\DateTimeInterface::RFC3339), $payload['createdAt']);
        self::assertSame($article->getUpdatedAt()?->format(\DateTimeInterface::RFC3339), $payload['updatedAt']);
    }

    private function normalized(Article $article): array
    {
        self::bootKernel();

        /** @var NormalizerInterface $normalizer */
        $normalizer = static::getContainer()->get('serializer');

        return $normalizer->normalize($article);
    }

    private function article(): Article
    {
        return Article::create(
            new ArticleId(self::ARTICLE_ID),
            'My article',
            'Article content...',
            new AuthorId(self::AUTHOR_ID),
            new CategoryId(self::CATEGORY_ID),
        );
    }
}
