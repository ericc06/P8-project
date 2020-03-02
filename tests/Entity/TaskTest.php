<?php

// tests/Controller/TaskTest.php

namespace App\Tests\Entity;

use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaskTest extends KernelTestCase
{
    private $task;
    private $manager;

    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->manager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->task = $this->manager
            ->getRepository(Task::class)
            ->findBy(['title' => 'Tâche Eric n°1'])[0];
    }

    public function testGetId()
    {
        $this->assertIsInt($this->task->getId());
        $this->assertGreaterThan(0, $this->task->getId());
    }

    public function testGetCreatedAt()
    {
        $now = new \Datetime();

        $this->task->setCreatedAt($now);
        $this->assertEquals($now, $this->task->getCreatedAt());
    }

    public function testGetTitle()
    {
        $this->assertEquals('Tâche Eric n°1', $this->task->getTitle());
    }

    public function testGetContent()
    {
        $this->assertEquals(
            'Ceci est la tâche n°1. Elle a été créée par Eric.',
            $this->task->getContent()
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        // avoid memory leaks
        unset($this->task);
        $this->manager->close();
        $this->manager = null;

        // Note: The entities inserted into the database during these tests
        // were not really thank to the "doctrine-test-bundle".
        // So, we don't have to remove thme from the database.
    }
}
