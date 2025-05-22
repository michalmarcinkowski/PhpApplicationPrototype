<?php

namespace App\Request;

class AddItemToCartRequest
{
    public $cartId;
    public $productId;
    public $quantity;
}
