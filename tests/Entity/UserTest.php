<?php
// tests/Controller/UserTest.php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use \Doctrine\Common\Collections\ArrayCollection;

class UserTest extends KernelTestCase
{
    private $user;
    private $manager;

    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->manager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->user = $this->manager
            ->getRepository(User::class)
            ->findBy(['username' => 'Eric'])[0];
    }

    public function testGetId()
    {
        $this->assertIsInt($this->user->getId());
        $this->assertGreaterThan(0, $this->user->getId());
    }

    public function testGetUsername()
    {
        $this->assertEquals('Eric', $this->user->getUsername());
    }

    public function testGetEmail()
    {
        $this->assertEquals('Eric@mail.loc', $this->user->getEmail());
    }

    public function testSetRoles()
    {
        // The user Eric has "ROLE_USER". We add him "ROLE_ADMIN"
        $this->user->setRoles(['ROLE_ADMIN']);

        $this->assertEquals('ROLE_ADMIN', $this->user->getRoles()[0]);
        $this->assertEquals(2, sizeof($this->user->getRoles()));
    }
    
    public function testGetRoles()
    {
        $this->assertEquals('ROLE_USER', $this->user->getRoles()[0]);
        $this->assertEquals(1, sizeof($this->user->getRoles()));
    }

    public function testSetTasks()
    {
        // At this point the user Eric owns 5 tasks. We will replace them
        // with 3 other new tasks.

        $tasks = new ArrayCollection();

        // Creating 3 tasks
        for ($t = 1; $t < 4; ++$t) {
            $task = new Task($this->user);
            $task->setTitle('Tâche n°'.$t);
            $task->setContent('Ceci est une tâche.');
            $tasks->add($task);
        }

        $this->user->setTasks($tasks);

        $this->assertEquals(3, $this->user->getTasks()->count());
    }

    public function testGetTasks()
    {
        $tasks = $this->user->getTasks();

        $this->assertEquals(5, $tasks->count());
        $this->assertInstanceOf(Task::class, $tasks->get(0));
    }

    public function testAddTasks()
    {
        $task = new Task($this->user);

        $task->setCreatedAt(new \Datetime());
        $task->setTitle('Tâche ajoutée');
        $task->setContent('Ceci est une tâche ajoutée.');

        $this->manager->persist($task);
        $this->manager->flush();

        $this->user->addTask($task);


        // Getting an ArrayCollection containing 6 tasks
        $tasks = $this->user->getTasks();

        $this->assertEquals(6, $tasks->count());
    }

    public function testRemoveTasks()
    {
        // Getting an ArrayCollection containing 5 tasks
        $tasks = $this->user->getTasks();

        // Getting the first task in collection and removing it
        /*\var_dump($tasks);
        $task = $tasks->first();
        $this->user->removeTask($task);

        // Getting an ArrayCollection containing 3 tasks
        $tasks = $this->user->getTasks();*/
        $task = $tasks->get(0);
        $this->user->removeTask($task);

        $this->assertEquals(4, $tasks->count());
    }

    protected function tearDown()
    {
        parent::tearDown();

        // avoid memory leaks
        unset($this->user);
        $this->manager->close();
        $this->manager = null;

        // Note: The entities inserted into the database during these tests
        // were not really thank to the "doctrine-test-bundle".
        // So, we don't have to remove thme from the database.
    }
}
