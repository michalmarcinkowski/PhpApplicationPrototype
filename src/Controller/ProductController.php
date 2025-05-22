<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Request\ProductCreateRequest;
use App\Request\ProductUpdateRequest;
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
    public const DEFAULT_PAGINATION = 3;
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

    #[Route('/api/products', name: 'app_product_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $limit = self::DEFAULT_PAGINATION;
        $page = (int) $request->query->get('page', 1);

        $totalProducts = $this->productRepository->count();
        $totalPages = (int) ceil($totalProducts / $limit);

        $currentPage = $this->resolveCurrentPage($totalProducts, $page, $totalPages);

        $products = $this->productRepository->findBy([], null, $limit, ($currentPage - 1) * $limit);

        $serializedProducts = $this->serializer->serialize($products, 'json');
        return new JsonResponse([
            'data' => json_decode($serializedProducts, true),
            'meta' => [
                'pagination' => [
                    'total' => $totalProducts,
                    'per_page' => $limit,
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages,
            ]]],
                Response::HTTP_OK,
            [
                'Content-Type' => 'application/json; charset=utf-8',
            ]);
    }

    #[Route('/api/products/{id}', name: 'app_product_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
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

        /** @var ProductUpdateRequest $productUpdateRequest */
        $productUpdateRequest = $this->serializer->deserialize(
            $request->getContent(),
            ProductUpdateRequest::class,
            'json'
        );

        $violations = $this->validator->validate($productUpdateRequest);
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
        if ($this->productRepository->findOneBy(['title' => $productUpdateRequest->title])) {
            return new JsonResponse([
                'message' => 'Product with this title already exists.'
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                [
                    'Content-Type' => 'application/json; charset=utf-8'
                ]);
        }

        $product->setTitle($productUpdateRequest->title);
        $product->setPrice($productUpdateRequest->price);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $product->getId(),
            'title' => $product->getTitle(),
            'price' => $product->getPrice(),
        ],
            Response::HTTP_OK,
        [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
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
        if ($this->productRepository->findOneBy(['title' => $productCreateRequest->title])) {
            return new JsonResponse([
                'message' => 'Product with this title already exists.'
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            [
                'Content-Type' => 'application/json; charset=utf-8'
            ]);
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

    public function resolveCurrentPage(int $totalProducts, int $page, int $totalPages): int
    {
        if ($totalProducts === 0) {
            return 1; // If no products, stay on page 1
        } else if ($page > $totalPages) {
            return $totalPages; // If page is greater than total pages, return last page
        }

        if ($page < 1) {
            return 1; // If page is less than 1, return first page
        }

        return $page;
    }
}
