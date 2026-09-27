<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Model;

/**
 * Gives an article its cover, from the bytes of an uploaded image, or takes
 * it away.
 */
final class ChangeArticleImageCommand
{
    private string $articleId;
    private ?string $image;

    /**
     * @param string|null $image the uploaded bytes; null takes the cover away
     */
    public function __construct(string $articleId, ?string $image)
    {
        $this->articleId = $articleId;
        $this->image = $image;
    }

    public function getArticleId(): string
    {
        return $this->articleId;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }
}
