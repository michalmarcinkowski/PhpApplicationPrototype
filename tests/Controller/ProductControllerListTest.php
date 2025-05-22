<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class ProductControllerListTest extends WebTestCase
{
    const DEFAULT_PAGINATION = 3;
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

    /**
     * Helper method to remove all products from the database.
     */
    private function clearProducts(): void
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();
        foreach ($products as $product) {
            $this->entityManager->remove($product);
        }
        $this->entityManager->flush();
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
            $product = new Product();
            $product->setTitle($data['title']);
            $product->setPrice($data['price']);
            $this->entityManager->persist($product);
        }
        $this->entityManager->flush();
    }

    private function createRandomProducts(int $numberOfProducts): void
    {
        for ($i = 1; $i <= $numberOfProducts; $i++) {
            $product = new Product();
            $product->setTitle('Game Title ' . $i);
            $product->setPrice(rand(99, 999));
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

        $this->client->request(method: 'GET', uri: '/api/products');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // Assert products data
        $this->assertArrayHasKey('products', $responseData);
        $this->assertIsArray($responseData['products']);
        $this->assertCount(self::DEFAULT_PAGINATION, $responseData['products']);

        // Assert pagination data
        $this->assertArrayHasKey('pagination', $responseData);
        $this->assertIsArray($responseData['pagination']);
        $this->assertSame(1, $responseData['pagination']['currentPage']);
        $this->assertDefaultPaginationGeneralInfo($responseData['pagination']);

        // Verify the titles of the first 3 products
        $this->assertSame('Fallout', $responseData['products'][0]['title']);
        $this->assertSame('Don\'t Starve', $responseData['products'][1]['title']);
        $this->assertSame('Baldur\'s Gate', $responseData['products'][2]['title']);
    }

    public function testListProductsSpecificPage(): void
    {
        $this->createFiveStandardProducts();
        $pageNumber = 2;

        $this->client->request(method: 'GET', uri: '/api/products?page=' . $pageNumber);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $responseData['products']);
        $this->assertSame('Icewind Dale', $responseData['products'][0]['title']);
        $this->assertSame('Bloodborne', $responseData['products'][1]['title']);

        $this->assertSame($pageNumber, $responseData['pagination']['currentPage']);
        $this->assertDefaultPaginationGeneralInfo($responseData['pagination']);
    }

    /**
     * Test listing products when the catalog is empty.
     */
    public function testListProductsEmptyCatalog(): void
    {
        $this->client->request(method: 'GET', uri: '/api/products');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('products', $responseData);
        $this->assertIsArray($responseData['products']);
        $this->assertEmpty($responseData['products']);

        $this->assertArrayHasKey('pagination', $responseData);
        $this->assertSame(1, $responseData['pagination']['currentPage']);
        $this->assertSame(self::DEFAULT_PAGINATION, $responseData['pagination']['itemsPerPage']);
        $this->assertSame(0, $responseData['pagination']['totalItems']);
        $this->assertSame(1, $responseData['pagination']['totalPages']);
    }

    public function testListProductsFewerThanLimit(): void
    {
        $numberOfProducts = 2;
        $this->createRandomProducts($numberOfProducts);

        $this->client->request(method: 'GET', uri: '/api/products');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // Assert products data
        $this->assertCount($numberOfProducts, $responseData['products']);
        $this->assertSame('Game Title 1', $responseData['products'][0]['title']);
        $this->assertSame('Game Title 2', $responseData['products'][1]['title']);

        // Assert pagination data
        $this->assertSame(1, $responseData['pagination']['currentPage']);
        $this->assertSame(self::DEFAULT_PAGINATION, $responseData['pagination']['itemsPerPage']);
        $this->assertSame($numberOfProducts, $responseData['pagination']['totalItems']);
        $this->assertSame(1, $responseData['pagination']['totalPages']);
    }

    public function testListProductsPageTooHigh(): void
    {
        $this->createFiveStandardProducts();

        // Request page 10, but there are only 2 pages
        $this->client->request(method: 'GET', uri: '/api/products?page=10');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // Should return the last page (page 2)
        $this->assertCount(1, $responseData['products']);
        $this->assertSame('Cyberpunk 2077', $responseData['products'][0]['title']);
        $this->assertSame(2, $responseData['pagination']['currentPage']); // Adjusted to last page
        $this->assertSame(self::DEFAULT_PAGINATION, $responseData['pagination']['itemsPerPage']);
        $this->assertSame(self::DEFAULT_NUMBER_OF_PRODUCTS, $responseData['pagination']['totalItems']);
        $this->assertSame(2, $responseData['pagination']['totalPages']);
    }

    public function testListProductsInvalidPageNumber(): void
    {
        $this->createFiveStandardProducts();

        // Test with page=0
        $this->client->request(method: 'GET', uri: '/api/products?page=0');
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $responseData['pagination']['currentPage']); // Should be adjusted to 1
        $this->assertSame('Fallout', $responseData['products'][0]['title']);

        // Test with page=-5
        $this->client->request(method: 'GET', uri: '/api/products?page=-5');
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $responseData['pagination']['currentPage']); // Should be adjusted to 1
        $this->assertSame('Fallout', $responseData['products'][0]['title']);
    }

//    public function testListProductsLimitCap(): void
//    {
//        $this->createFiveStandardProducts();
//
//        // Request limit 10, but it should be capped at 3
//        $this->client->request(method: 'GET', uri: '/api/products?limit=10');
//
//        $this->assertResponseIsSuccessful();
//        $responseData = json_decode($this->client->getResponse()->getContent(), true);
//
//        // Should only return 3 products
//        $this->assertCount(self::PAGINATION_DEFAULT, $responseData['products']);
//        // Should be capped at 3
//        $this->assertSame(self::PAGINATION_DEFAULT, $responseData['pagination']['itemsPerPage']);
//        $this->assertSame(1, $responseData['pagination']['currentPage']);
//        $this->assertSame(self::TOTAL_NUMBER_OF_PRODUCTS, $responseData['pagination']['totalItems']);
//        $this->assertSame(2, $responseData['pagination']['totalPages']);
//    }

    protected function assertDefaultPaginationGeneralInfo(array $pagination): void
    {
        $this->assertSame(self::DEFAULT_PAGINATION, $pagination['itemsPerPage']);
        $this->assertSame(self::DEFAULT_NUMBER_OF_PRODUCTS, $pagination['totalItems']);
        $this->assertSame(ceil(self::DEFAULT_NUMBER_OF_PRODUCTS / self::DEFAULT_PAGINATION), $pagination['totalPages']);
    }


    protected function resetDb(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }
}
