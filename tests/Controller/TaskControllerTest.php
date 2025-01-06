<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class TaskControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    	
	// On vide la table des tâches avant chaque test        
	
	$connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $connection->executeStatement($platform->getTruncateTableSQL('task', true));
    }

    public function testCreateTask(): void
    {
	// On crée une nouvelle tâche via l'API
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Test task',
                'description' => 'Test description',
                'status' => 'todo'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('Test task', $responseData['title']);
    }

    public function testListTasks(): void
    {
        $this->client->request('GET', '/api/tasks');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('page', $responseData);
    }

    public function testUpdateTask(): void
    {
	// On crée d'abord une tâche pour pouvoir la modifier
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Task to update',
                'description' => 'This task will be updated',
                'status' => 'todo'
            ])
        );
        $task = json_decode($this->client->getResponse()->getContent(), true);
    	// On modifie le statut de la tâche
        $this->client->request(
            'PUT',
            '/api/tasks/' . $task['id'],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'status' => 'in_progress'
            ])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('in_progress', $responseData['status']);
    }
    public function testDeleteTask(): void
{
    $this->client->request(
        'POST',
        '/api/tasks',
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode([
            'title' => 'Task to delete',
            'description' => 'This task will be deleted',
            'status' => 'todo'
        ])
    );
    $task = json_decode($this->client->getResponse()->getContent(), true);

    $this->client->request('DELETE', '/api/tasks/' . $task['id']);
    $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

    $this->client->request('GET', '/api/tasks');
    $responseData = json_decode($this->client->getResponse()->getContent(), true);

    $taskIds = array_map(function($task) {
        return $task['id'];
    }, $responseData['data']);

    $this->assertNotContains($task['id'], $taskIds);
}

    public function testSearchTasks(): void
    {
        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Unique search title',
                'description' => 'Description for search',
                'status' => 'todo'
            ])
        );

        $this->client->request('GET', '/api/tasks?search=Unique');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals('Unique search title', $responseData['data'][0]['title']);
    }
}
