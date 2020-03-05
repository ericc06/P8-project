<?php

// tests/Controller/DefaultControllerTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DefaultControllerTest extends WebTestCase
{
    private $client = null;

    protected function setUp()
    {
        $this->client = static::createClient();
        // Disabling catching of exceptions in the test client
        // to allow the exceptions to be reported by PHPUnit
        $this->client->catchExceptions(false);
    }

    public function testIndexNotLoggedIn()
    {
        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/');

        // When requesting the "/" URL, we should be redirected (with status code 302)
        // to the login form page.
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/login$/', $client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testIndexLoggedInAsUser()
    {
        $this->logInAsUser();

        $this->client->request('GET', '/');

        //$this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    private function logInAsUser()
    {
        $session = self::$container->get('session');

        $firewallName = 'main';
        // if you don't define multiple connected firewalls, the context defaults to the firewall name
        // See https://symfony.com/doc/current/reference/configuration/security.html#firewall-context
        $firewallContext = 'main';

        // you may need to use a different token class depending on your application.
        // for example, when using Guard authentication you must instantiate PostAuthenticationGuardToken
        $token = new UsernamePasswordToken('eric', 'eric', $firewallName, ['ROLE_USER']);
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    protected function tearDown()
    {
        parent::tearDown();

        // avoid memory leaks
        $this->client = null;
    }
}
