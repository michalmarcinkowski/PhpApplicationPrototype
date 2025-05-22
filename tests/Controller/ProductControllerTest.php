<?php

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProductControllerTest extends WebTestCase
{
    protected ?KernelBrowser $client = null;


    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        $this->resetDb();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
    }

    public function testCreateProduct(): void
    {
        $testProductData = [
            'title' => 'Go',
            'price' => 199,
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');

        $this->assertArrayHasKey('id', $responseContent);
        $this->assertSame($testProductData['title'], $responseContent['title']);
        $this->assertSame($testProductData['price'], $responseContent['price']);
    }

    public function testCreateProductWithZeroPrice(): void
    {
        $testProductData = [
            'title' => 'Fallout',
            'price' => 0,
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');

        $this->assertArrayHasKey('id', $responseContent);
        $this->assertSame($testProductData['title'], $responseContent['title']);
        $this->assertSame($testProductData['price'], $responseContent['price']);
    }

    public function testCantCreateProductWithNullTitle(): void
    {
        $testProductData = [
            'title' => null,
            'price' => 199,
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertValidationError($responseContent, 'title');
    }

    public function testCantCreateProductWithNotStringTitle(): void
    {
        $testProductData = [
            'title' => 1234,
            'price' => 199,
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertValidationError($responseContent, 'title');
    }

    public function testCantCreateProductWithAlreadyExistingTitle(): void
    {
        $testProductData = [
            'title' => 'Go',
            'price' => 199,
        ];
        $this->createProductJsonRequest($testProductData);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->createProductJsonRequest($testProductData);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCantCreateProductWithBlankTitle(): void
    {
        $testProductData = [
            'title' => '',
            'price' => 199,
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertValidationError($responseContent, 'title');
    }

    public function testCantCreateProductWithTitleShorterThanTwoChars(): void
    {
        $testProductData = [
            'title' => 'A',
            'price' => 199,
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertValidationError($responseContent, 'title');
    }

    public function testCantCreateProductWithTitleLongerThanTwoHundredFiftyFive(): void
    {
        $testProductData = [
            'title' => '256characterslongstringxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'price' => 199,
        ];
        $responseData = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertValidationError($responseData, 'title');

    }

    public function testCantCreateProductWithNullPrice(): void
    {
        $testProductData = [
            'title' => 'Fallout',
            'price' => null,
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertValidationError($responseContent, 'price');
    }

    public function testCantCreateProductWithStringPrice(): void
    {
        $testProductData = [
            'title' => 'Fallout',
            'price' => '1',
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertValidationError($responseContent, 'price');
    }

    public function testCantCreateProductWithNegativePrice(): void
    {
        $testProductData = [
            'title' => 'Fallout',
            'price' => -1,
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertValidationError($responseContent, 'price');
    }

    public function testCantCreateProductWithGreaterThanIntegerMaxPrice(): void
    {
        $testProductData = [
            'title' => 'Fallout',
            'price' => 9223372036854775808, // integer overflow number (PHP_INT_MAX + 1) on 64-bit systems
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertValidationError($responseContent, 'price');
    }

    public function testCantDeleteNonExistingProduct(): void
    {
        $this->deleteProductJsonRequest(111);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');
    }

    public function testCanDeleteNewlyCreatedProduct(): void
    {
        $testProductData = [
            'title' => 'Go',
            'price' => 199,
        ];
        $createProductResponseContent = $this->createProductJsonRequest($testProductData);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertArrayHasKey('id', $createProductResponseContent);

        $this->deleteProductJsonRequest($createProductResponseContent['id']);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testCantDeleteTwiceTheSameProduct(): void
    {
        $testProductData = [
            'title' => 'Go',
            'price' => 199,
        ];
        $createProductResponseContent = $this->createProductJsonRequest($testProductData);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertArrayHasKey('id', $createProductResponseContent);

        $this->deleteProductJsonRequest($createProductResponseContent['id']);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->deleteProductJsonRequest($createProductResponseContent['id']);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');
    }
    public function testCanUpdateProductSuccessfully(): void
    {
        $testProductData = [
            'title' => 'Icwind Dale',
            'price' => 499,
        ];
        $updatedProductData = [
            'title' => 'Icewind Dale',
            'price' => 599,
        ];

        $createdProductResponse = $this->createProductJsonRequest($testProductData);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertArrayHasKey('id', $createdProductResponse);
        // Check that the newly created product data is not the same as the data in update request
        $this->assertNotSame($updatedProductData['title'], $createdProductResponse['title']);
        $this->assertNotSame($updatedProductData['price'], $createdProductResponse['price']);

        $updatedProductResponse = $this->updateProductJsonRequest($createdProductResponse['id'], $updatedProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame($createdProductResponse['id'], $updatedProductResponse['id']);
        $this->assertSame($updatedProductData['title'], $updatedProductResponse['title']);
        $this->assertSame($updatedProductData['price'], $updatedProductResponse['price']);
    }

    public function testCantUpdateProductWithDuplicateTitle(): void
    {
        $testProduct1Data = [
            'title' => 'Unique Title',
            'price' => 299,
        ];
        $testProduct2Data = [
            'title' => 'Icewind Dale',
            'price' => 499,
        ];
        $nonUniqueTitleData = [
            'title' => 'Unique Title',
            'price' => 199,
        ];

        $this->createProductJsonRequest($testProduct1Data);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $createdProductResponse = $this->createProductJsonRequest($testProduct2Data);
        $this->assertArrayHasKey('id', $createdProductResponse);

        $updatedProductResponse = $this->updateProductJsonRequest($createdProductResponse['id'], $nonUniqueTitleData);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSame(['message' => 'Product with this title already exists.'], $updatedProductResponse);
    }

    public function testCantUpdateNonExistentProduct(): void
    {
        $nonExistentProductData = [
            'title' => 'Non Existent',
            'price' => 100,
        ];

        $updatedProductResponse = $this->updateProductJsonRequest(1111, $nonExistentProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertSame(['message' => 'Product not found.'], $updatedProductResponse);
    }

    protected function assertValidationError(array $response, string $field): void
    {
        $this->assertSame('Validation Failed', $response['message']);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey($field, $response['errors']);
    }

    protected function createProductJsonRequest(array $payload): mixed
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/products',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode($payload)
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    protected function deleteProductJsonRequest(int $id): mixed
    {
        $this->client->request(
            method: 'DELETE',
            uri: sprintf('/api/products/%d', $id),
            server: [
                'HTTP_ACCEPT' => 'application/json',
            ],
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function updateProductJsonRequest($id, array $payload): mixed
    {
        $this->client->request(
            method: 'PUT',
            uri: '/api/products/' . $id,
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode($payload)
        );

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    protected function resetDb(): void
    {
        $container = self::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }
}
