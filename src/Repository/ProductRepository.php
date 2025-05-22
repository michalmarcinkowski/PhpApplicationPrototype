<?php

namespace App\Repository;

use App\Entity\Product;
use App\Factory\ProductFactoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository implements ProductRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findById(int $id): ?Product
    {
        return $this->find($id);
    }

    public function findOneByTitle(string $title): ?Product
    {
        return $this->findOneBy(['title' => $title]);
    }

    public function getPaginator(int $perPage, int $requestedPage): Paginator
    {
        return new Paginator($this->createQueryBuilder('p'), $perPage, $requestedPage);
    }
}
