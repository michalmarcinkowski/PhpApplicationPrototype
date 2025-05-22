<?php

namespace Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use phpDocumentor\Reflection\DocBlock\Tags\See;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CartControllerTest extends WebTestCase
{
    protected ?KernelBrowser $client = null;
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->resetDb();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up the database after each test
        $this->resetDb();

        // Close the entity manager to prevent memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testCanCreateCart(): void
    {
        $responseContent = $this->createCartRequest();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');

        $this->assertArrayHasKey('id', $responseContent);
//        $this->assertArrayHasKey('items', $responseContent);

//        $this->assertResponseHeaderSame('Location', sprintf('/api/carts/%d', $responseContent['id']));
    }

    public function testCanGetEmptyCart(): void
    {
        $cartCreatedResponse = $this->createCartRequest();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertArrayHasKey('id', $cartCreatedResponse);

        $cartId = $cartCreatedResponse['id'];

        $getCartRequestResponse = $this->getCartRequest($cartId);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');

        $this->assertSame($cartCreatedResponse['id'], $getCartRequestResponse['id']);
//        $this->assertArrayHasKey('items', $getCartRequestResponse);
    }

    public function testCantGetNonExistentCart(): void
    {
        $this->getCartRequest(9999);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');
    }

    public function testCanCreateCartAndSuccessfullyAddOneItemToIt(): void
    {
        $product = $this->givenThereIsAProduct('Fallout', 199);
        $cartCreatedResponse = $this->createCartRequest();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');

        $this->assertArrayHasKey('id', $cartCreatedResponse);
        $this->assertArrayHasKey('items', $cartCreatedResponse);
        $this->assertEmpty($cartCreatedResponse['items']);

        $addedToCartResponse = $this->addItemToCartRequest($cartCreatedResponse['id'], $product->getId(), 1);
dump($addedToCartResponse);
        $this->assertArrayHasKey('id', $addedToCartResponse);
        $this->assertArrayHasKey('items', $addedToCartResponse);
        $this->assertCount(1, $addedToCartResponse['items']);

        $this->assertSame('Fallout', $addedToCartResponse['items'][0]['title']);
        $this->assertSame(1, $addedToCartResponse['items'][0]['quantity']);
        $this->assertSame(199, $addedToCartResponse['items'][0]['price']);
        $this->assertSame(199, $addedToCartResponse['items'][0]['total']);
        $this->assertSame(199, $addedToCartResponse['total']);
    }

    protected function createCartRequest(): mixed
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/carts/',
            server: [
                'HTTP_ACCEPT' => 'application/json',
            ],
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    protected function addItemToCartRequest(int $cartId, int $productId, int $quantity): mixed
    {
        $this->client->request(
            method: 'POST',
            uri: sprintf('/api/carts/%d/items/', $cartId),
            server: [
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode(['cartId' =>$cartId, 'productId' => $productId, 'quantity' => $quantity]),
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    protected function givenThereIsAProduct(string $title, int $price): Product
    {
        $product = Product::create($title, $price);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    protected function getCartRequest(mixed $cartId): mixed
    {
        $this->client->request(
            method: 'GET',
            uri: sprintf('/api/carts/%d', $cartId),
            server: [
                'HTTP_ACCEPT' => 'application/json',
            ],
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

//    protected function deleteCartItemJsonRequest(int $cartId, int $id): mixed
//    {
//        $this->client->request(
//            method: 'DELETE',
//            uri: sprintf('/api/carts/%d/items/%d', $cartId, $id), // item or product ID??
//            server: [
//                'HTTP_ACCEPT' => 'application/json',
//            ],
//        );
//
//        return json_decode($this->client->getResponse()->getContent(), true);
//    }

    protected function resetDb(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }
}
