<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    private Task $task;

    protected function setUp(): void
    {
        // On crée une nouvelle tâche avant chaque test
        parent::setUp();
        $this->task = new Task();
    }

    public function testDefaultValues(): void
    {
        $this->assertEquals('todo', $this->task->getStatus());
    }

    public function testSetAndGetTitle(): void
    {
        $title = "Test Task";
        $this->task->setTitle($title);
        $this->assertEquals($title, $this->task->getTitle());
    }

    public function testSetAndGetDescription(): void
    {
        $description = "Test Description";
        $this->task->setDescription($description);
        $this->assertEquals($description, $this->task->getDescription());
    }

    public function testSetAndGetStatus(): void
    {
        $status = "in_progress";
        $this->task->setStatus($status);
        $this->assertEquals($status, $this->task->getStatus());
    }

    public function testCreatedAtAndUpdatedAtAreInitializedOnPrePersist(): void
    {
        // On vérifie que les dates sont bien créées à l'initialisation
        $this->task->setCreatedAtValue();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->task->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->task->getUpdatedAt());
    }

    public function testUpdatedAtIsModifiedOnPreUpdate(): void
    {
        // On vérifie que la date de mise à jour change bien
        $this->task->setCreatedAtValue();
        $originalUpdatedAt = $this->task->getUpdatedAt();
        
        sleep(1); // On attend 1 seconde pour avoir un timestamp différent
        
        $this->task->setUpdatedAtValue();
        $this->assertNotEquals($originalUpdatedAt, $this->task->getUpdatedAt());
    }
}
