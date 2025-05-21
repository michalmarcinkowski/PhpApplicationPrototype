<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProductControllerTest extends WebTestCase
{
    public function testCreateProduct(): void
    {
        $testProductData = [
            'title' => 'Go',
            'price' => 199,
        ];
        $responseContent = $this->createProductJsonRequest($testProductData);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');
#        $this->assertResponseHeaderSame('Location', sprintf('/api/products/%d', $responseContent['id']));

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
#        $this->assertResponseHeaderSame('Location', sprintf('/api/products/%d', $responseContent['id']));

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

    /**
     * @param array $response
     * @param string $field
     *
     * @return void
     */
    protected function assertValidationError(array $response, string $field): void
    {
        $this->assertSame('Validation Failed', $response['message']);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey($field, $response['errors']);
    }

    /**
     * @param array $payload
     *
     * @return mixed
     */
    protected function createProductJsonRequest(array $payload): mixed
    {
        $client = ProductControllerTest::createClient();

        $client->request(
            method: 'POST',
            uri: '/api/products',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode($payload)
        );

        return json_decode($client->getResponse()->getContent(), true);
    }
}
