<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Repository\ProductRepositoryInterface;
use App\Request\ProductCreateRequest;
use App\Request\ProductUpdateRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CartController extends AbstractController
{
    public const DEFAULT_RESPONSE_CONTENT_TYPE = 'application/json; charset=utf-8';
    private SerializerInterface $serializer;
//    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private ProductRepositoryInterface $productRepository;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
//        ProductRepositoryInterface $productRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->serializer = $serializer;
//        $this->validator = $validator;
//        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/carts', name: 'app_cart_create', methods: ['POST'])]
    public function createAction(): JsonResponse
    {
        $cart = $this->createCart();
        $serializedCart = $this->serializer->serialize($cart, 'json');

        return $this->getJsonResponseFromJsonData($serializedCart, Response::HTTP_CREATED);
    }

    protected function createCart(): Cart
    {
        $cart = new Cart();
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    protected function getJsonResponseFromJsonData(string $jsonPayload, int $responseCode, string $contentType = self::DEFAULT_RESPONSE_CONTENT_TYPE): JsonResponse
    {
        return new JsonResponse($jsonPayload, $responseCode, ['Content-Type' => $contentType], true);
    }
}
