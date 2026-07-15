<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/** Almacén en memoria para pruebas unitarias. */
final class InMemoryAttemptCounterStore implements AttemptCounterStore
{
    /** @var array<string, array{count: int, windowStart: int}> */
    private array $counters = [];

    public function count(string $key, int $windowSeconds): int
    {
        $entry = $this->counters[$key] ?? null;
        if ($entry === null || $entry['windowStart'] <= time() - $windowSeconds) {
            return 0;
        }
        return $entry['count'];
    }

    public function increment(string $key, int $windowSeconds): void
    {
        $entry = $this->counters[$key] ?? null;
        if ($entry === null || $entry['windowStart'] <= time() - $windowSeconds) {
            $this->counters[$key] = ['count' => 1, 'windowStart' => time()];
            return;
        }
        $this->counters[$key]['count']++;
    }

    public function reset(string $key): void
    {
        unset($this->counters[$key]);
    }
}
