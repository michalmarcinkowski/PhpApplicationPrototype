<?php

namespace App\Repository;

use Doctrine\ORM\QueryBuilder;

class Paginator extends \Doctrine\ORM\Tools\Pagination\Paginator implements \JsonSerializable
{
    public function __construct(QueryBuilder $queryBuilder, int $perPage, int $requestedPage)
    {
        parent::__construct($queryBuilder);

        $this->getQuery()->setMaxResults($perPage);
        $page = $this->resolveCurrentPage($this->getTotal(), $requestedPage, $this->getTotalPages());
        $this->getQuery()->setFirstResult($this->calculateOffset($page));
    }

    public function getTotal(): int
    {
        return $this->count();
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->getTotal() / $this->getQuery()->getMaxResults());
    }

    public function getPerPage(): int
    {
        return $this->getQuery()->getMaxResults();
    }

    public function getCurrentPage(): int
    {
        $offset = $this->getQuery()->getFirstResult();
        $perPage = $this->getPerPage();

        if ($offset % $perPage !== 0) {
            throw new \LogicException('Invalid offset. Offset must be a multiple of perPage to get current page.');
        }

        return ((int) ($offset / $perPage)) + 1;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'data' => $this->getIterator()->getArrayCopy(),
            'meta' => [
                'pagination' => [
                    'total' => $this->getTotal(),
                    'per_page' => $this->getPerPage(),
                    'current_page' => $this->getCurrentPage(),
                    'total_pages' => $this->getTotalPages()
                ]
            ]
        ];
    }

    protected function calculateOffset(int $page): int
    {
        return (int) (($page - 1) * $this->getPerPage());
    }

    protected function resolveCurrentPage(int $total, int $page, int $totalPages): int
    {
        if ($total === 0) {
            return 1; // If no items, stay on page 1
        } else if ($page > $totalPages) {
            return $totalPages; // If page is greater than total pages, return last page
        }

        if ($page < 1) {
            return 1; // If page is less than 1, return first page
        }

        return $page;
    }
}
