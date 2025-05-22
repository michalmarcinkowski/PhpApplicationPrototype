<?php

namespace App\Repository;

class Pagination implements PaginationInterface
{
    private int $total;
    private int $perPage;
    private int $currentPage;
    private int $totalPages;

    public function __construct(int $total, int $perPage, int $requestedPage)
     {
         $this->perPage = $perPage;
         $this->total = $total;
         $this->totalPages = (int) ceil($total / $perPage);
         $this->currentPage = $this->resolveCurrentPage($total, $requestedPage, $this->totalPages);
     }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getOffset(): int
    {
        return (int) (($this->getCurrentPage() - 1) * $this->getPerPage());
    }

    public function jsonSerialize(): mixed
    {
        return [
             'total' => $this->total,
             'per_page' => $this->perPage,
             'current_page' => $this->currentPage,
             'total_pages' => $this->totalPages,
         ];
    }

    protected function resolveCurrentPage(int $total, int $page, int $totalPages): int
    {
        if ($total === 0) {
            return 1; // If no products, stay on page 1
        } else if ($page > $totalPages) {
            return $totalPages; // If page is greater than total pages, return last page
        }

        if ($page < 1) {
            return 1; // If page is less than 1, return first page
        }

        return $page;
    }
}