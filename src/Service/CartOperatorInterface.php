<?php

namespace App\Service;

use App\Entity\Cart;

interface CartOperatorInterface
{
    public function addProductToCart(int $cartId, int $productId, int $quantity): Cart;
}
