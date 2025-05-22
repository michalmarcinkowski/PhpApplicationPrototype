<?php

namespace App\Entity;
use Webmozart\Assert\Assert;

class CartItem implements \JsonSerializable
{
    private ?int $id = null;
    private ?Cart $cart = null;
    private ?Product $product = null;
    private ?int $quantity = null;

    public static function create(Cart $cart, Product $product, int $quantity): CartItem
    {
        $cartItem = new self();
        $cartItem->cart = $cart;
        $cartItem->product = $product;
        $cartItem->quantity = $quantity;

        return $cartItem;
    }

    public static function createWithoutCart(Product $product, int $quantity): CartItem
    {
        $cartItem = new self();
        $cartItem->product = $product;
        $cartItem->quantity = $quantity;

        return $cartItem;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function getTotal(): int
    {
        Assert::notNull($this->product, 'Cant calculate total for cart item without product');
        return $this->product->getPrice() * $this->quantity;
    }

    public function increaseQuantity(int $quantity): void
    {
        Assert::lessThanEq($this->quantity + $quantity, 10);
        $this->quantity += $quantity;
    }

    public function setCart(?Cart $cart): void
    {
        $this->cart = $cart;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->product?->getTitle(),
            'price' => $this->product?->getPrice(),
            'quantity' => $this->quantity,
            'total' => $this->getTotal()
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
