<?php

// tests/Controller/SecurityControllerTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityControllerTest extends WebTestCase
{
    private $client = null;

    protected function setUp()
    {
        $this->client = static::createClient();
    }

    public function testHomepageAccessNotLoggedIn()
    {
        // Disabling catching of exceptions in the test client
        // to allow the exceptions to be reported by PHPUnit
        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/');

        // Restoring catchExceptions to true for the next tests
        $this->client->catchExceptions(true);
    }

    public function testLoginAsAdmin()
    {
        $this->client->request('GET', '/');

        $this->client->followRedirect();

        $this->client->submitForm('Se connecter', [
            '_username' => 'admin',
            '_password' => '@D31n7wd',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertRegExp('/\/$/', $crawler->getUri());
        $this->assertSelectorTextContains('div.username', 'admin');
        $this->assertSelectorTextContains('div.top-buttons-bar > a', 'Créer un utilisateur');
    }

    public function testLoginAsUser()
    {
        $this->client->request('GET', '/');

        $this->client->followRedirect();

        $this->client->submitForm('Se connecter', [
            '_username' => 'eric',
            '_password' => 'eric',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertRegExp('/\/$/', $crawler->getUri());
        $this->assertSelectorTextContains('div.username', 'Eric');
        $this->assertSelectorTextNotContains('div.top-buttons-bar > a', 'Créer un utilisateur');
        $this->assertSelectorTextContains('div.top-buttons-bar > a', 'Se déconnecter');
        $this->assertSelectorTextContains('div.task-buttons-bar > a', 'Créer une nouvelle tâche');
    }

    public function testLogout()
    {
        // We first log in
        $this->client->request('GET', '/');

        $this->client->followRedirect();

        $this->client->submitForm('Se connecter', [
            '_username' => 'admin',
            '_password' => '@D31n7wd',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertRegExp('/\/$/', $crawler->getUri());
        $this->assertSelectorTextContains('div.username', 'admin');

        // Then we log out
        $this->client->request('GET', '/logout');

        $crawler = $this->client->followRedirect();

        $this->assertRegExp('/\/$/', $crawler->getUri());

        $crawler = $this->client->followRedirect();

        $this->assertRegExp('/\/login$/', $crawler->getUri());
    }

    protected function tearDown()
    {
        parent::tearDown();

        // avoid memory leaks
        $this->client = null;
    }
}
