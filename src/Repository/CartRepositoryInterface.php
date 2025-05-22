<?php

namespace App\Repository;

use App\Entity\Cart;

interface CartRepositoryInterface
{
    public function findById(int $id): ?Cart;
}
