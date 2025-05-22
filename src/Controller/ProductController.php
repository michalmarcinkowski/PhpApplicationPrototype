<?php

namespace App\Controller;

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

final class ProductController extends AbstractController
{
    public const DEFAULT_PAGINATION = 3;
    public const DEFAULT_RESPONSE_CONTENT_TYPE = 'application/json; charset=utf-8';
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private ProductRepositoryInterface $productRepository;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        ProductRepositoryInterface $productRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/products', name: 'app_product_list', methods: ['GET'])]
    public function listAction(Request $request): JsonResponse
    {
        $paginator = $this->productRepository->getPaginator(
            self::DEFAULT_PAGINATION,
            $request->query->getInt('page', 1),
        );
        $serializedPaginatedProducts = $this->serializer->serialize($paginator, 'json');

        return $this->getJsonResponseFromJsonData($serializedPaginatedProducts, Response::HTTP_OK);
    }

    #[Route('/api/products', name: 'app_product_create', methods: ['POST'])]
    public function createAction(Request $request): JsonResponse
    {
        /** @var ProductCreateRequest $productCreateRequest */
        $productCreateRequest = $this->getProductCreateRequest($request->getContent());

        $jsonResponse = $this->validateCreateRequest($productCreateRequest);
        if ($jsonResponse) {
            return $jsonResponse;
        }
        $product = $this->create($productCreateRequest);
        $serializedProduct = $this->serializer->serialize($product, 'json');

        return $this->getJsonResponseFromJsonData($serializedProduct, Response::HTTP_CREATED);
    }

    #[Route('/api/products/{id}', name: 'app_product_update', methods: ['PUT'])]
    public function updateAction(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return $this->createNotFoundJsonResponse('Product not found.');
        }

        $productUpdateRequest = $this->getProductUpdateRequest($request->getContent());
        $jsonResponse = $this->validateUpdateRequest($productUpdateRequest);
        if ($jsonResponse) {
            return $jsonResponse;
        }

        $this->update($product, $productUpdateRequest);

        $serializedProduct = $this->serializer->serialize($product, 'json');
        return $this->getJsonResponseFromJsonData($serializedProduct, Response::HTTP_OK);
    }

    #[Route('/api/products/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    public function deleteAction(int $id): JsonResponse
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return $this->createNotFoundJsonResponse('Product not found.');
        }
        $this->delete($product);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    protected function validateCreateRequest(ProductCreateRequest $productCreateRequest): ?JsonResponse
    {
        return $this->fullValidation($this->validator->validate($productCreateRequest), $productCreateRequest->title);
    }

    protected function validateUpdateRequest(ProductUpdateRequest $productUpdateRequest): ?JsonResponse
    {
        return $this->fullValidation($this->validator->validate($productUpdateRequest), $productUpdateRequest->title);
    }

    protected function fullValidation(ConstraintViolationListInterface $violations, ?string $title): ?JsonResponse
    {
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $error) {
                $errors[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->createValidationFailedResponse($errors);
        }
        if (null === $title) {
            return $this->createValidationFailedResponse(['title' => ['Title cannot be null']]);
        }
        if ($this->productRepository->findOneByTitle($title)) {
            return $this->createValidationFailedResponse(['title' => 'Product with this title already exists.']);
        }

        return null;
    }

    protected function getJsonResponseFromArrayData(array $arrayPayload, int $responseCode, string $contentType = self::DEFAULT_RESPONSE_CONTENT_TYPE): JsonResponse
    {
        return new JsonResponse($arrayPayload, $responseCode, ['Content-Type' => $contentType]);
    }

    protected function getJsonResponseFromJsonData(string $jsonPayload, int $responseCode, string $contentType = self::DEFAULT_RESPONSE_CONTENT_TYPE): JsonResponse
    {
        return new JsonResponse($jsonPayload, $responseCode, ['Content-Type' => $contentType], true);
    }

    protected function createNotFoundJsonResponse(string $message): JsonResponse
    {
        return $this->getJsonResponseFromArrayData(['message' => $message], Response::HTTP_NOT_FOUND);
    }

    protected function createValidationFailedResponse(array $errors): JsonResponse
    {
        return $this->getJsonResponseFromArrayData([
            'message' => 'Validation Failed',
            'errors' => $errors
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function create(ProductCreateRequest $productCreateRequest): Product
    {
        $product = Product::create($productCreateRequest->title, $productCreateRequest->price);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    /**
     * @param Product $product
     * @return void
     */
    protected function delete(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    /**
     * @param Product $product
     * @param ProductUpdateRequest $productUpdateRequest
     * @return void
     */
    protected function update(Product $product, ProductUpdateRequest $productUpdateRequest): void
    {
        $product->setTitle($productUpdateRequest->title);
        $product->setPrice($productUpdateRequest->price);
        $this->entityManager->flush();
    }

    protected function getProductCreateRequest($content): mixed
    {
        return $this->serializer->deserialize(
            $content, ProductCreateRequest::class, 'json'
        );
    }

    public function getProductUpdateRequest($content): ProductUpdateRequest
    {
        return $this->serializer->deserialize(
            $content, ProductUpdateRequest::class, 'json'
        );
    }
}
