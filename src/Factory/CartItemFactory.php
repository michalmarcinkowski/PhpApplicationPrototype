<?php

namespace App\Factory;

use App\Entity\CartItem;
use App\Repository\CartRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use Webmozart\Assert\Assert;

class CartItemFactory implements CartItemFactoryInterface
{
    private CartRepositoryInterface $cartRepository;
    private ProductRepositoryInterface $productRepository;

    public function __construct(CartRepositoryInterface $cartRepository, ProductRepositoryInterface $productRepository)
    {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
    }

    public function createCartItem(int $cartId, int $productId, int $quantity): CartItem
    {
        $cart = $this->cartRepository->findById($cartId);
        Assert::notNull($cart, sprintf('Cart with ID %d not found', $cartId));
        $product = $this->productRepository->findById($productId);
        Assert::notNull($product, sprintf('Product with ID %d not found', $productId));

        return CartItem::create($cart, $product, $quantity);
    }

    public function createCartItemWithoutCart(int $productId, int $quantity): CartItem
    {
        $product = $this->productRepository->findById($productId);
        Assert::notNull($product, sprintf('Product with ID %d not found', $productId));

        return CartItem::createWithoutCart($product, $quantity);
    }
}