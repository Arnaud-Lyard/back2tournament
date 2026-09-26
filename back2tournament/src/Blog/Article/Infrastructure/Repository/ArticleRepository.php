<?php
// src/Blog/Article/Infrastructure/Repository/ArticleRepository.php

declare(strict_types=1);

namespace App\Blog\Article\Infrastructure\Repository;

use App\Blog\Article\Domain\Entity\Article;
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

    public function findPage(?string $search, ?string $categoryId, int $limit, int $offset): array
    {
        return $this->filtered($search, $categoryId)
            ->orderBy('article.createdAt', 'DESC')
            ->addOrderBy('article.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countPage(?string $search, ?string $categoryId): int
    {
        return (int) $this->filtered($search, $categoryId)
            ->select('COUNT(article.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function filtered(?string $search, ?string $categoryId): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('article');

        if (null !== $search) {
            $queryBuilder
                ->andWhere('LOWER(article.title) LIKE :search OR LOWER(article.body) LIKE :search')
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
