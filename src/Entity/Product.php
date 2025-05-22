<?php

namespace App\Entity;

class Product
{
    private ?int $id = null;

    private ?string $title = null;

    private ?int $price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function create(string $title, int $price): self
    {
        $product = new self();
        $product->setTitle($title);
        $product->setPrice($price);

        return $product;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }
}
