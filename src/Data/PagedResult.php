<?php

declare(strict_types=1);

namespace Encuestas\Data;

/**
 * Una página de resultados junto con la información necesaria para paginar en la vista.
 *
 * @template T
 */
final class PagedResult
{
    /** @param list<T> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly int $pageSize,
        public readonly int $totalCount,
    ) {
    }

    public function totalPages(): int
    {
        return $this->pageSize <= 0 ? 0 : (int) ceil($this->totalCount / $this->pageSize);
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->totalPages();
    }
}
