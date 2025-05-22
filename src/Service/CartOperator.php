<?php

namespace App\Service;

use App\Entity\Cart;
use App\Factory\CartItemFactoryInterface;
use App\Repository\CartRepositoryInterface;
use Webmozart\Assert\Assert;

class CartOperator implements CartOperatorInterface
{
    private CartRepositoryInterface $cartRepository;
    private CartItemFactoryInterface $cartItemFactory;

    public function __construct(CartRepositoryInterface $cartRepository, CartItemFactoryInterface $cartItemFactory)
    {
        $this->cartRepository = $cartRepository;
        $this->cartItemFactory = $cartItemFactory;
    }

    public function addProductToCart(int $cartId, int $productId, int $quantity): Cart
    {
        $cart = $this->cartRepository->findById($cartId);
        Assert::notNull($cart, sprintf('Cart with ID [%d] not found', $cartId));

        $cartItem = $this->cartItemFactory->createCartItemWithoutCart($productId, $quantity);
        $cart->addOrMerge($cartItem);

        return $cart;
    }
}