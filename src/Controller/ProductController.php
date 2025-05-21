<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Request\ProductCreateRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductController extends AbstractController
{
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private ProductRepository $productRepository;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/products/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return new JsonResponse([
                'message' => 'Product not found.'
            ],
                Response::HTTP_NOT_FOUND,
            [
                'Content-Type' => 'application/json; charset=utf-8'
            ]);
        }
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/products', name: 'app_product_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var ProductCreateRequest $productCreateRequest */
        $productCreateRequest = $this->serializer->deserialize(
            $request->getContent(),
            ProductCreateRequest::class,
            'json'
        );

        $violations = $this->validator->validate($productCreateRequest);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $error) {
                $errors[$error->getPropertyPath()][] = $error->getMessage();
            }

            return new JsonResponse([
                'message' => 'Validation Failed',
                'errors' => $errors
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $product = new Product();
        $product->setTitle($productCreateRequest->title);
        $product->setPrice($productCreateRequest->price);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $product->getId(),
            'title' => $product->getTitle(),
            'price' => $product->getPrice(),
        ],
            Response::HTTP_CREATED,
        [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }
}
