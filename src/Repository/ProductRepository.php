<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function getPaginated(PaginationInterface $pagination): array
    {
        return parent::findBy([], null, $pagination->getPerPage(), $pagination->getOffset());
    }

    public function getPagination(int $perPage, int $requestedPage): PaginationInterface
    {
        return new Pagination($this->count(), $perPage, $requestedPage);
    }
}
