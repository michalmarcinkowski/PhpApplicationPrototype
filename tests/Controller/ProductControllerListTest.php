<?php

namespace App\Tests\Controller;

use App\Controller\ProductController;
use App\Entity\Product;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class ProductControllerListTest extends WebTestCase
{
    const DEFAULT_NUMBER_OF_PRODUCTS = 5;
    protected ?KernelBrowser $client = null;
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
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

    private function createFiveStandardProducts(): void
    {
        $productsData = [
            ['title' => 'Fallout', 'price' => 199],
            ['title' => 'Don\'t Starve', 'price' => 299],
            ['title' => 'Baldur\'s Gate', 'price' => 399],
            ['title' => 'Icewind Dale', 'price' => 499],
            ['title' => 'Bloodborne', 'price' => 599],
        ];

        foreach ($productsData as $data) {
            $product = Product::create($data['title'], $data['price']);
            $this->entityManager->persist($product);
        }
        $this->entityManager->flush();
    }

    private function createRandomProducts(int $numberOfProducts): void
    {
        for ($i = 1; $i <= $numberOfProducts; $i++) {
            $product = Product::create('Game Title ' . $i, rand(99, 999));
            $this->entityManager->persist($product);
        }
        $this->entityManager->flush();
    }

    /**
     * Test listing products with default pagination (first page).
     */
    public function testListProductsDefaultPagination(): void
    {
        $this->createFiveStandardProducts();

        $this->client->request(method: 'GET', uri: '/api/products/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // Assert products data
        $this->assertArrayHasKey('data', $responseData);
        $this->assertIsArray($responseData['data']);
        $this->assertCount(ProductController::DEFAULT_PAGINATION, $responseData['data']);

        // Assert pagination data
        $this->assertArrayHasKey('meta', $responseData);
        $this->assertArrayHasKey('pagination', $responseData['meta']);
        $pagination = $responseData['meta']['pagination'];
        $this->assertIsArray($pagination);
        $this->assertSame(1, $pagination['current_page']);
        $this->assertDefaultPaginationGeneralInfo($pagination);

        // Verify the titles of the first 3 products
        $this->assertSame('Fallout', $responseData['data'][0]['title']);
        $this->assertSame('Don\'t Starve', $responseData['data'][1]['title']);
        $this->assertSame('Baldur\'s Gate', $responseData['data'][2]['title']);
    }

    public function testListProductsSpecificPage(): void
    {
        $this->createFiveStandardProducts();
        $pageNumber = 2;

        $this->client->request(method: 'GET', uri: '/api/products/?page=' . $pageNumber);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $responseData['data']);
        $this->assertSame('Icewind Dale', $responseData['data'][0]['title']);
        $this->assertSame('Bloodborne', $responseData['data'][1]['title']);

        $this->assertSame($pageNumber, $responseData['meta']['pagination']['current_page']);
        $this->assertDefaultPaginationGeneralInfo($responseData['meta']['pagination']);
    }

    /**
     * Test listing products when the catalog is empty.
     */
    public function testListProductsEmptyCatalog(): void
    {
        $this->client->request(method: 'GET', uri: '/api/products/');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertIsArray($responseData['data']);
        $this->assertEmpty($responseData['data']);

        $this->assertArrayHasKey('meta', $responseData);
        $pagination = $responseData['meta']['pagination'];
        $this->assertSame(1, $pagination['current_page']);
        $this->assertSame(ProductController::DEFAULT_PAGINATION, $pagination['per_page']);
        $this->assertSame(0, $pagination['total']);
        $this->assertSame(0, $pagination['total_pages']);
    }

    public function testListProductsFewerThanLimit(): void
    {
        $numberOfProducts = 2;
        $this->createRandomProducts($numberOfProducts);

        $this->client->request(method: 'GET', uri: '/api/products/');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // Assert products data
        $this->assertCount($numberOfProducts, $responseData['data']);
        $this->assertSame('Game Title 1', $responseData['data'][0]['title']);
        $this->assertSame('Game Title 2', $responseData['data'][1]['title']);

        // Assert pagination data
        $this->assertArrayHasKey('meta', $responseData);
        $pagination = $responseData['meta']['pagination'];
        $this->assertSame(1, $pagination['current_page']);
        $this->assertSame(ProductController::DEFAULT_PAGINATION, $pagination['per_page']);
        $this->assertSame($numberOfProducts, $pagination['total']);
        $this->assertSame(1, $pagination['total_pages']);
    }

    public function testListProductsPageTooHigh(): void
    {
        $this->createFiveStandardProducts();

        // Request page 10, but there are only 2 pages
        $this->client->request(method: 'GET', uri: '/api/products/?page=10');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // Should return the last page (page 2)
        $this->assertCount(2, $responseData['data']);
        $pagination = $responseData['meta']['pagination'];
        $this->assertSame(2, $pagination['current_page']); // Adjusted to last page
        $this->assertSame(ProductController::DEFAULT_PAGINATION, $pagination['per_page']);
        $this->assertSame(self::DEFAULT_NUMBER_OF_PRODUCTS, $pagination['total']);
        $this->assertSame(2, $pagination['total_pages']);
    }

    public function testListProductsInvalidPageNumber(): void
    {
        $this->createFiveStandardProducts();

        // Test with page=0
        $this->client->request(method: 'GET', uri: '/api/products/?page=0');
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $responseData['meta']['pagination']['current_page']); // Should be adjusted to 1
        $this->assertSame('Fallout', $responseData['data'][0]['title']);

        // Test with page=-5
        $this->client->request(method: 'GET', uri: '/api/products/?page=-5');
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $responseData['meta']['pagination']['current_page']); // Should be adjusted to 1
        $this->assertSame('Fallout', $responseData['data'][0]['title']);
    }

    protected function assertDefaultPaginationGeneralInfo(array $pagination): void
    {
        $this->assertSame(ProductController::DEFAULT_PAGINATION, $pagination['per_page']);
        $this->assertSame(self::DEFAULT_NUMBER_OF_PRODUCTS, $pagination['total']);
        $this->assertSame((int) ceil(self::DEFAULT_NUMBER_OF_PRODUCTS / ProductController::DEFAULT_PAGINATION), $pagination['total_pages']);
    }


    protected function resetDb(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }
}
