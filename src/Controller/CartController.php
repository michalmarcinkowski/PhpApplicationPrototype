<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Repository\CartRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Request\AddItemToCartRequest;
use App\Request\ProductCreateRequest;
use App\Request\ProductUpdateRequest;
use App\Service\CartOperatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/carts', name: 'app_cart_')]
final class CartController extends AbstractJsonApiController
{
    public const DEFAULT_RESPONSE_CONTENT_TYPE = 'application/json; charset=utf-8';
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private CartRepositoryInterface $cartRepository;
    private CartOperatorInterface $cartOperator;
    private EntityManagerInterface $entityManager;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        CartRepositoryInterface $cartRepository,
        CartOperatorInterface $cartOperator,
        EntityManagerInterface $entityManager
    )
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->cartRepository = $cartRepository;
        $this->cartOperator = $cartOperator;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    public function createAction(): JsonResponse
    {
        $cart = $this->createCart();
        $serializedCart = $this->serializer->serialize($cart, 'json');

        return $this->getJsonResponseFromJsonData($serializedCart, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function showAction(int $id): JsonResponse
    {
        $cart = $this->cartRepository->findById($id);
        if (!$cart) {
            return $this->createNotFoundJsonResponse('Cart not found.');
        }
        $serializedCart = $this->serializer->serialize($cart, 'json');

        return $this->getJsonResponseFromJsonData($serializedCart, Response::HTTP_OK);
    }

    #[Route('/{cartId}/items/', name: 'add', methods: ['POST'])]
    public function add(int $cartId, Request $request): JsonResponse
    {
        $addItemToCartRequest = $this->getAddItemToCartRequest($request->getContent());
        $jsonResponse = $this->validateAddToCartRequest($addItemToCartRequest);
        if ($jsonResponse) {
            return $jsonResponse;
        }

        $cart = $this->cartOperator->addProductToCart(
            $addItemToCartRequest->cartId,
            $addItemToCartRequest->productId,
            $addItemToCartRequest->quantity
        );
        $this->entityManager->flush();
        $serializedCart = $this->serializer->serialize($cart, 'json');

        return $this->getJsonResponseFromJsonData($serializedCart, Response::HTTP_CREATED);
    }

    protected function validateAddToCartRequest(AddItemToCartRequest $addItemToCartRequest): ?JsonResponse
    {
        $violations = $this->validator->validate($addItemToCartRequest);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $error) {
                $errors[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->createValidationFailedResponse($errors);
        }

        return null;
    }

    protected function getAddItemToCartRequest($content): AddItemToCartRequest
    {
        return $this->serializer->deserialize(
            $content, AddItemToCartRequest::class, 'json'
        );
    }

    protected function createCart(): Cart
    {
        $cart = new Cart();
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }
}
