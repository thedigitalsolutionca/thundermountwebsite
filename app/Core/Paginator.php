<?php

declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    public int $pages;

    public function __construct(
        public int $total,
        public int $perPage = 12,
        public int $current = 1
    ) {
        $this->perPage = max(1, $perPage);
        $this->pages = max(1, (int) ceil($total / $this->perPage));
        $this->current = min(max(1, $current), $this->pages);
    }

    public function offset(): int
    {
        return ($this->current - 1) * $this->perPage;
    }

    public function from(): int
    {
        return $this->total === 0 ? 0 : $this->offset() + 1;
    }

    public function to(): int
    {
        return min($this->total, $this->offset() + $this->perPage);
    }

    /**
     * @return array<int,int|string>
     */
    public function links(int $window = 2): array
    {
        $links = [];
        for ($i = 1; $i <= $this->pages; $i++) {
            if ($i === 1 || $i === $this->pages || abs($i - $this->current) <= $window) {
                $links[] = $i;
            } elseif (end($links) !== '…') {
                $links[] = '…';
            }
        }
        return $links;
    }

    public function hasPrevious(): bool
    {
        return $this->current > 1;
    }

    public function hasNext(): bool
    {
        return $this->current < $this->pages;
    }
}
