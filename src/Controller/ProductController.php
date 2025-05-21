<?php

namespace App\Controller;

use App\Entity\Product;
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

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/products/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    public function delete(Product $product): JsonResponse
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return new JsonResponse([],Response::HTTP_NO_CONTENT);
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

        return new JsonResponse([
            'id' => 1,
            'title' => $productCreateRequest->title,
            'price' => $productCreateRequest->price,
        ],
            Response::HTTP_CREATED,
            [
                'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }
}
