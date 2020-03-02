<?php

// tests/Controller/UserControllerTest.php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserControllerTest extends WebTestCase
{
    private $client = null;
    private $manager;

    protected function setUp()
    {
        $this->client = static::createClient();
        // Disabling catching of exceptions in the test client
        // to allow the exceptions to be reported by PHPUnit
        $this->client->catchExceptions(false);

        $kernel = self::bootKernel();

        $this->manager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testListUsersNotLoggedIn()
    {
        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/users');

        // When requesting the "/users" URL not being logged in,
        // we should be redirected (with status code 302) to the login form page.
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/login$/', $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testListUsersAsAdmin()
    {
        $this->logInAsAdmin();
        $this->client->request('GET', '/users');

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
    }

    public function testListUsersAsUser()
    {
        $this->logInAsUser();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionCode(403);

        $this->client->request('GET', '/users');

        // When requesting the "/user" URL while being connected as user,
        // we should be redirected (with status code 403) to access denied exception.
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
        //$this->assertRegExp('/login$/', $client->getResponse()->headers->get('Location'));
        //$this->assertResponseRedirects('https://example.com', 301);
    }

    public function testCreateUserAccessNotLoggedIn()
    {
        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/users/create');

        // When requesting the "/users/create" URL not being logged in,
        // we should be redirected (with status code 302) to the login form page.
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/login$/', $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testCreateUserAccessAsAdmin()
    {
        $this->logInAsAdmin();
        $this->client->request('GET', '/users/create');

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Créer un utilisateur');
    }

    public function testCreateUserAccessAsUser()
    {
        $this->logInAsUser();

        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/users/create');

        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateUserSubmitWithRole()
    {
        $this->logInAsAdmin();
        $this->client->request('GET', '/users/create');

        $this->client->submitForm('Ajouter', [
            'user[username]' => 'Tom',
            'user[password][first]' => 'tom_pwd',
            'user[password][second]' => 'tom_pwd',
            'user[email]' => 'tom@mail.loc',
            'user[roles]' => 'ROLE_USER',
        ]);

        $this->client->followRedirect();

        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        //$this->assertSelectorTextContains('td', 'tom@mail.loc');
    }

    public function testCreateUserSubmitWithoutRole()
    {
        $this->logInAsAdmin();
        $this->client->request('GET', '/users/create');

        $this->client->submitForm('Ajouter', [
            'user[username]' => 'Tom',
            'user[password][first]' => 'tom_pwd',
            'user[password][second]' => 'tom_pwd',
            'user[email]' => 'tom@mail.loc',
        ]);

        $this->client->followRedirect();

        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        //$this->assertSelectorTextContains('td', 'tom@mail.loc');
    }

    public function testEditUserAccessNotLoggedIn()
    {
        $user_id = self::getUserIdByUsername('Eric');

        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/users/'.$user_id.'/edit');

        // When requesting the user edit URL not being logged in,
        // we should be redirected (with status code 302) to the login form page.
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/login$/', $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testEditUserAccessAsAdmin()
    {
        $user_id = self::getUserIdByUsername('admin');

        $this->logInAsAdmin();
        $this->client->request('GET', '/users/'.$user_id.'/edit');

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier ');
        $this->assertSelectorTextContains('strong', 'admin');
    }

    public function testEditUserAccessAsUser()
    {
        $user_id = self::getUserIdByUsername('Eric');

        $this->logInAsUser();

        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/users/'.$user_id.'/edit');

        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
    }

    public function testEditUserSubmit()
    {
        $user_id = self::getUserIdByUsername('Eric');

        $this->logInAsAdmin();
        $this->client->request('GET', '/users/'.$user_id.'/edit');

        $this->client->submitForm('Modifier', [
            'user[username]' => 'Eric modif',
            'user[email]' => 'eric-modif@mail.loc',
            'user[roles]' => 'ROLE_USER',
        ]);

        $this->client->followRedirect();

        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        //$this->assertSelectorTextContains('td', 'eric-modif@mail.loc');
    }

    private function logInAsAdmin()
    {
        $session = self::$container->get('session');

        $firewallName = 'main';
        // if you don't define multiple connected firewalls, the context defaults to the firewall name
        // See https://symfony.com/doc/current/reference/configuration/security.html#firewall-context
        $firewallContext = 'main';

        // you may need to use a different token class depending on your application.
        // for example, when using Guard authentication you must instantiate PostAuthenticationGuardToken
        $token = new UsernamePasswordToken('admin', 'admin', $firewallName, ['ROLE_ADMIN']);
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
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

    private function getUserIdByUsername(string $username)
    {
        return $this->manager
            ->getRepository(User::class)
            ->findBy(['username' => $username])[0]
            ->getId();
    }

    protected function tearDown()
    {
        parent::tearDown();

        // avoid memory leaks
        $this->client = null;
        $this->session = null;
        $this->token = null;
        $this->cookie = null;
        $this->manager->close();
        $this->manager = null;
    }
}
