<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Webmozart\Assert\Assert;

class Cart implements \JsonSerializable
{
    private ?int $id = null;

    /**
     * @var Collection<int, CartItem>
     */
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addOrMerge(CartItem $cartItem): void
    {
        if ($this->items->contains($cartItem)) {
            $cartItem->setCart($this);
            return;
        }

        $cartItemProduct = $cartItem->getProduct();
        Assert::notNull($cartItemProduct, 'Cant add cart item without product to cart');
        foreach ($this->items as $item) {
            if ($item->getProduct() === $cartItemProduct) {
                $item->increaseQuantity($cartItem->getQuantity());
                $cartItem->setCart(null); // remove the cart reference from this item
                return;
            }
        }

        $this->items->add($cartItem);
        $cartItem->setCart($this);
    }

    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->items as $cartItem) {
            $total += $cartItem->getTotal();
        }

        return $total;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'items' => array_map(
                static fn(CartItem $cartItem) => $cartItem->toArray(),
                $this->items->toArray()
            ),
            'total' => $this->getTotal(),
        ];
    }
}
