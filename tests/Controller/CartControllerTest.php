<?php

namespace Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CartControllerTest extends WebTestCase
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

    public function testCreateCart(): void
    {
        $responseContent = $this->createCartJsonRequest();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');

        $this->assertArrayHasKey('id', $responseContent);
//        $this->assertArrayHasKey('items', $responseContent);

//        $this->assertResponseHeaderSame('Location', sprintf('/api/carts/%d', $responseContent['id']));
    }

    protected function createCartJsonRequest(): mixed
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/carts',
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
        $container = self::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }
}
