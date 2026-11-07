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
    public const DEFAULT_PAGE_SIZE = 10;

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

    /**
     * Corrige valores fuera de rango que lleguen desde la URL.
     *
     * @return array{int, int}
     */
    public static function normalize(int $page, int $pageSize): array
    {
        return [max(1, $page), $pageSize < 1 ? self::DEFAULT_PAGE_SIZE : $pageSize];
    }
}
