<?php

namespace App\Tests\Entity;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

class CartItemTest extends TestCase
{
    public function testIncreaseQuantity(): void
    {
        $initialQuantity = 2;
        $increaseAmount = 3;

        $mockCart = $this->createMock(Cart::class);
        $mockProduct = $this->createMock(Product::class);

        $cartItem = CartItem::create($mockCart, $mockProduct, $initialQuantity);

        $cartItem->increaseQuantity($increaseAmount);
        $this->assertSame($initialQuantity + $increaseAmount, $cartItem->getQuantity());
    }

    public function testIncreaseQuantityThrowsExceptionWhenExceedsTen(): void
    {
        $initialQuantity = 8;
        $increaseAmount = 3; // This will make total quantity 11

        $mockCart = $this->createMock(Cart::class);
        $mockProduct = $this->createMock(Product::class);

        $cartItem = CartItem::create($mockCart, $mockProduct, $initialQuantity);

        $this->expectException(InvalidArgumentException::class);
        $cartItem->increaseQuantity($increaseAmount);
    }

    public function testIncreaseQuantityToExactlyTen(): void
    {
        $initialQuantity = 5;
        $increaseAmount = 5; // Total quantity becomes 10

        $mockCart = $this->createMock(Cart::class);
        $mockProduct = $this->createMock(Product::class);

        $cartItem = CartItem::create($mockCart, $mockProduct, $initialQuantity);
        $cartItem->increaseQuantity($increaseAmount);
        $this->assertSame(10, $cartItem->getQuantity());
    }
}
