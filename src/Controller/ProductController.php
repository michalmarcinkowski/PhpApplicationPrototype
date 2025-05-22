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
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductController extends AbstractController
{
    public const DEFAULT_PAGINATION = 3;
    public const RESPONSE_CONTENT_TYPE = 'application/json; charset=utf-8';
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private ProductRepository $productRepository;

    public function __construct(
        SerializerInterface    $serializer,
        ValidatorInterface     $validator,
        ProductRepository      $productRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/products', name: 'app_product_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $pagination = $this->productRepository->getPagination(
            self::DEFAULT_PAGINATION,
            (int) $request->query->get('page', 1),
        );

        $products = $this->productRepository->getPaginated($pagination);

        $serializedProducts = $this->serializer->serialize($products, 'json');
        return $this->getJsonResponseFromArrayData([
            'data' => json_decode($serializedProducts, true),
            'meta' => [
                'pagination' => $pagination,
            ]], Response::HTTP_OK);
    }

    #[Route('/api/products/{id}', name: 'app_product_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->getJsonResponseFromArrayData([
                'message' => 'Product not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        /** @var ProductUpdateRequest $productUpdateRequest */
        $productUpdateRequest = $this->serializer->deserialize(
            $request->getContent(),
            ProductUpdateRequest::class,
            'json'
        );
        $jsonResponse = $this->validateUpdateRequest($productUpdateRequest);
        if ($jsonResponse) {
            return $jsonResponse;
        }

        $product->setTitle($productUpdateRequest->title);
        $product->setPrice($productUpdateRequest->price);
        $this->entityManager->flush();

        $serializedProduct = $this->serializer->serialize($product, 'json');
        return $this->getJsonResponseFromJsonData($serializedProduct, Response::HTTP_OK);
    }

    #[Route('/api/products/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->getJsonResponseFromArrayData(
                ['message' => 'Product not found.'],
                Response::HTTP_NOT_FOUND
            );
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

        $jsonResponse = $this->validateCreateRequest($productCreateRequest);
        if ($jsonResponse) {
            return $jsonResponse;
        }

        $product = new Product();
        $product->setTitle($productCreateRequest->title);
        $product->setPrice($productCreateRequest->price);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $serializedProduct = $this->serializer->serialize($product, 'json');
        return $this->getJsonResponseFromJsonData($serializedProduct, Response::HTTP_CREATED);
    }

    protected function validateCreateRequest(ProductCreateRequest $productCreateRequest): ?JsonResponse
    {
        return $this->fullValidation($this->validator->validate($productCreateRequest), $productCreateRequest->title);
    }

    protected function validateUpdateRequest(ProductUpdateRequest $productUpdateRequest): ?JsonResponse
    {
        return $this->fullValidation($this->validator->validate($productUpdateRequest), $productUpdateRequest->title);
    }

    public function fullValidation(ConstraintViolationListInterface $violations, ?string $title): ?JsonResponse
    {
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $error) {
                $errors[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->getJsonResponseFromArrayData([
                'message' => 'Validation Failed',
                'errors' => $errors
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (null === $title) {
            return $this->getJsonResponseFromArrayData([
                'message' => 'Validation Failed',
                'errors' => ['title' => ['Title cannot be null']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($this->productRepository->findOneBy(['title' => $title])) {
            return $this->getJsonResponseFromArrayData([
                'message' => 'Product with this title already exists.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return null;
    }

    protected function getJsonResponseFromArrayData(array $arrayPayload, int $responseCode, string $contentType = self::RESPONSE_CONTENT_TYPE): JsonResponse
    {
        return new JsonResponse($arrayPayload, $responseCode, ['Content-Type' => $contentType]);
    }

    protected function getJsonResponseFromJsonData(string $jsonPayload, int $responseCode, string $contentType = self::RESPONSE_CONTENT_TYPE): JsonResponse
    {
        return new JsonResponse($jsonPayload, $responseCode, ['Content-Type' => $contentType], true);
    }
}
