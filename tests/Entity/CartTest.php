<?php

namespace App\Tests\Entity;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class CartTest extends TestCase
{
    public function testConstructorInitializesItemsAsArrayCollection(): void
    {
        $cart = new Cart();
        $this->assertInstanceOf(ArrayCollection::class, $cart->getItems());
        $this->assertTrue($cart->getItems()->isEmpty());
    }

    public function testMergeAddsNewCartItemWhenEmptyCollectionAndProductIsNotPresent(): void
    {
        $cart = new Cart();
        $product = $this->createMock(Product::class);
        $cartItem = $this->createMock(CartItem::class);
        $cartItem->method('getProduct')->willReturn($product);
        $cartItem->expects($this->never())->method('increaseQuantity');
        $cartItem->expects($this->once())->method('setCart');

        $cart->addOrMerge($cartItem);

        $this->assertTrue($cart->getItems()->contains($cartItem));
    }

    public function testMergeAddsNewCartItemWhenProductIsNotPresentWithinExistingItems(): void
    {
        $cart = new Cart();
        $product1 = $this->createMock(Product::class);
        $product2 = $this->createMock(Product::class);

        // Existing cart item in the cart
        $existingCartItem = $this->createMock(CartItem::class);
        $existingCartItem->method('getProduct')->willReturn($product1);
        $existingCartItem->expects($this->never())->method('increaseQuantity');

        // New existing item to prior to merge
        $cart->getItems()->add($existingCartItem);

        $cartItem = $this->createMock(CartItem::class);
        $cartItem->method('getProduct')->willReturn($product2);
        $cartItem->expects($this->never())->method('increaseQuantity');

        $cart->addOrMerge($cartItem);

        $this->assertCount(2, $cart->getItems());
        $this->assertTrue($cart->getItems()->contains($existingCartItem));
        $this->assertTrue($cart->getItems()->contains($cartItem));
        $this->assertNotSame($cartItem, $existingCartItem);
    }

    public function testMergeIncreasesQuantityOfExistingCartItemWithSameProduct(): void
    {
        $cart = new Cart();
        $product = $this->createMock(Product::class);

        // Existing cart item in the cart
        $existingCartItem = $this->createMock(CartItem::class);
        $existingCartItem->method('getProduct')->willReturn($product);
        $existingCartItem->expects($this->once())
            ->method('increaseQuantity')
            ->with(5); // Expect existing item to increase quantity by 5

        // New cart item to merge
        $newCartItem = $this->createMock(CartItem::class);
        $newCartItem->method('getProduct')->willReturn($product);
        $newCartItem->method('getQuantity')->willReturn(5);

        // Expect new item's cart reference to be nulled
        $newCartItem->expects($this->once())->method('setCart')->with(null);
        // Manually add the existing item to the cart's collection
        $cart->getItems()->add($existingCartItem);

        // Ensure `contains` returns false for the new item so the loop is entered
//        $cart->getItems()->method('contains')->with($newCartItem)->willReturn(false);

        $cart->addOrMerge($newCartItem);

        // Assert that the collection still contains only the original item
        $this->assertCount(1, $cart->getItems());
        $this->assertTrue($cart->getItems()->contains($existingCartItem));
        $this->assertFalse($cart->getItems()->contains($newCartItem)); // The new item was not added, its quantity was merged
    }

    public function testMergeDoesNothingWhenCartItemIsAlreadyPresent(): void
    {
        $cart = new Cart();
        $product = $this->createMock(Product::class);

        $cartItem = $this->createMock(CartItem::class);
        $cartItem->method('getProduct')->willReturn($product);
        $cartItem->expects($this->never())->method('increaseQuantity'); // No quantity increase expected

        // Simulate the item already being in the collection
        $cart->getItems()->add($cartItem); // Add it directly to simulate presence

        $initialQuantity = $cartItem->getQuantity();
        $cart->addOrMerge($cartItem);

        $this->assertCount(1, $cart->getItems());
        $this->assertTrue($cart->getItems()->contains($cartItem));
        $this->assertSame($initialQuantity, $cartItem->getQuantity()); // Ensure quantity remains unchanged
    }

    public function testMergeThrowsExceptionWhenCartItemHasNoProduct(): void
    {
        $cart = new Cart();
        $cartItem = $this->createMock(CartItem::class);
        $cartItem->method('getProduct')->willReturn(null); // Simulate no product

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cant add cart item without product to cart');

        $cart->addOrMerge($cartItem);
    }
}