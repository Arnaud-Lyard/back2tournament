<?php
// src/Blog/Article/Infrastructure/Repository/ArticleRepository.php

declare(strict_types=1);

namespace App\Blog\Article\Infrastructure\Repository;

use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Enum\ArticleStatus;
use App\Blog\Article\Domain\Repository\ArticleRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository implements ArticleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function save(Article $article): void
    {
        $this->getEntityManager()->persist($article);
        $this->getEntityManager()->flush();
    }

    public function findPage(?string $search, ?string $categoryId, ?ArticleStatus $status, int $limit, int $offset): array
    {
        // Published articles by publication date; a draft by the day it was written.
        return $this->filtered($search, $categoryId, $status)
            ->addSelect('COALESCE(article.publishedAt, article.createdAt) AS HIDDEN listedAt')
            ->orderBy('listedAt', 'DESC')
            ->addOrderBy('article.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countPage(?string $search, ?string $categoryId, ?ArticleStatus $status): int
    {
        return (int) $this->filtered($search, $categoryId, $status)
            ->select('COUNT(article.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function filtered(?string $search, ?string $categoryId, ?ArticleStatus $status): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('article');

        if (null !== $status) {
            $queryBuilder
                ->andWhere('article.status = :status')
                ->setParameter('status', $status);
        }

        if (null !== $search) {
            $queryBuilder
                // In either language.
                ->andWhere('LOWER(article.title) LIKE :search OR LOWER(article.body) LIKE :search OR LOWER(article.titleEn) LIKE :search OR LOWER(article.bodyEn) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        if (null !== $categoryId) {
            $queryBuilder
                ->andWhere('article.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        return $queryBuilder;
    }
}
