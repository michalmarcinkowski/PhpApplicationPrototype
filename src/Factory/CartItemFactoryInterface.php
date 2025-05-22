<?php

namespace App\Factory;

use App\Entity\CartItem;

interface CartItemFactoryInterface
{
    public function createCartItem(int $cartId, int $productId, int $quantity): CartItem;
    public function createCartItemWithoutCart(int $productId, int $quantity): CartItem;
}
