<?php

namespace App\Repository;

use App\Entity\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;
    public function findOneByTitle(string $title): ?Product;
    public function getPaginator(int $perPage, int $requestedPage): Paginator;
}
