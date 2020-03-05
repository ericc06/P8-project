<?php

// tests/Controller/UserControllerTest.php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserControllerTest extends WebTestCase
{
    private $client = null;
    private $manager;

    protected function setUp()
    {
        $this->client = static::createClient();

        $kernel = self::bootKernel();

        $this->manager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testListUsersNotLoggedInException()
    {
        // Disabling catching of exceptions in the test client
        // to allow the exceptions to be reported by PHPUnit
        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/users');

        // No other assertions to be checked because expectException()
        // stops assertion checking.

        // Restoring catchExceptions to true for the next tests
        $this->client->catchExceptions(true);
    }

    public function testListUsersNotLoggedInRedirect()
    {
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
        $this->requestAsAdmin('/users');

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
    }

    public function testListUsersAsUserException()
    {
        // Disabling catching of exceptions in the test client
        // to allow the exceptions to be reported by PHPUnit
        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionCode(403);

        $this->requestAsUser('/users');

        // No other assertions to be checked because expectException()
        // stops assertion checking.

        // Restoring catchExceptions to true for the next tests
        $this->client->catchExceptions(true);
    }

    public function testCreateUserAccessNotLoggedInException()
    {
        // Disabling catching of exceptions in the test client
        // to allow the exceptions to be reported by PHPUnit
        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/users/create');

        // No other assertions to be checked because expectException()
        // stops assertion checking.

        // Restoring catchExceptions to true for the next tests
        $this->client->catchExceptions(true);
    }

    public function testCreateUserAccessNotLoggedInRedirect()
    {
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
        $this->requestAsAdmin('/users/create');

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Créer un utilisateur');
    }

    public function testCreateUserAccessAsUser()
    {
        // Disabling catching of exceptions in the test client
        // to allow the exceptions to be reported by PHPUnit
        $this->client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $this->requestAsUser('/users/create');

        // No other assertions to be checked because expectException()
        // stops assertion checking.

        // Restoring catchExceptions to true for the next tests
        $this->client->catchExceptions(true);
    }

    public function testCreateUserSubmitWithUserRole()
    {
        $this->requestAsAdmin('/users/create');

        $this->client->submitForm('Ajouter', [
            'user[username]' => 'Tom',
            'user[password][first]' => 'tom_pwd',
            'user[password][second]' => 'tom_pwd',
            'user[email]' => 'tom@mail.loc',
            'user[roles]' => 'ROLE_USER',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        $this->assertRegExp('/\/users$/', $crawler->getUri());
        // Counting the number of "Utilisateurs". We should have 4.
        $count = preg_match_all(
            '/<td>Utilisateur<\/td>/',
            $this->client->getResponse()->getContent()
        );
        $this->assertEquals(4, $count);
    }

    public function testCreateUserSubmitWithAdminRole()
    {
        $this->requestAsAdmin('/users/create');

        $this->client->submitForm('Ajouter', [
            'user[username]' => 'Jim',
            'user[password][first]' => 'jim_pwd',
            'user[password][second]' => 'jim_pwd',
            'user[email]' => 'jim@mail.loc',
            'user[roles]' => 'ROLE_ADMIN',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        $this->assertRegExp('/\/users$/', $crawler->getUri());
        // Counting the number of "Administrateur". We should have 2.
        $count = preg_match_all(
            '/<td>Administrateur<\/td>/',
            $this->client->getResponse()->getContent()
        );
        $this->assertEquals(2, $count);
    }

    public function testCreateUserSubmitWithoutRole()
    {
        $this->requestAsAdmin('/users/create');

        $this->client->submitForm('Ajouter', [
            'user[username]' => 'Tom',
            'user[password][first]' => 'tom_pwd',
            'user[password][second]' => 'tom_pwd',
            'user[email]' => 'tom@mail.loc',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        $this->assertRegExp('/\/users$/', $crawler->getUri());
        // Counting the number of "Utilisateurs". We should have 4.
        $count = preg_match_all(
            '/<td>Utilisateur<\/td>/',
            $this->client->getResponse()->getContent()
        );
        $this->assertEquals(4, $count);
    }

    public function testEditUserAccessNotLoggedInException()
    {
        // Disabling catching of exceptions in the test client
        // to allow the exceptions to be reported by PHPUnit
        $this->client->catchExceptions(false);

        $user_id = self::getUserIdByUsername('Eric');

        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/users/'.$user_id.'/edit');

        // No other assertions to be checked because expectException()
        // stops assertion checking.

        // Restoring catchExceptions to true for the next tests
        $this->client->catchExceptions(true);
    }

    public function testEditUserAccessNotLoggedInRedirect()
    {
        $user_id = self::getUserIdByUsername('Eric');

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

        $this->requestAsAdmin('/users/'.$user_id.'/edit');

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier ');
        $this->assertSelectorTextContains('strong', 'admin');
    }

    public function testEditUserAccessAsUserException()
    {
        // Disabling catching of exceptions in the test client
        // to allow the exceptions to be reported by PHPUnit
        $this->client->catchExceptions(false);

        $user_id = self::getUserIdByUsername('Eric');

        $this->expectException(AccessDeniedException::class);

        $this->requestAsUser('/users/'.$user_id.'/edit');

        // No other assertions to be checked because expectException()
        // stops assertion checking.

        // Restoring catchExceptions to true for the next tests
        $this->client->catchExceptions(true);
    }

    public function testEditUserSubmit()
    {
        $user_id = self::getUserIdByUsername('Eric');

        $this->requestAsAdmin('/users/'.$user_id.'/edit');

        $this->client->submitForm('Modifier', [
            'user[username]' => 'Eric modif',
            'user[email]' => 'eric-modif@mail.loc',
            'user[roles]' => 'ROLE_ADMIN',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
        $this->assertRegExp('/\/users$/', $crawler->getUri());
        // Counting the number of "Administrateur". We should have 2.
        $count = preg_match_all(
            '/<td>Administrateur<\/td>/',
            $this->client->getResponse()->getContent()
        );
        $this->assertEquals(2, $count);
    }

    private function getUserIdByUsername(string $username)
    {
        return $this->manager
            ->getRepository(User::class)
            ->findBy(['username' => $username])[0]
            ->getId();
    }

    private function requestAsAdmin(string $url)
    {
        return $this->client->request('GET', $url, [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);
    }

    private function requestAsUser(string $url)
    {
        return $this->client->request('GET', $url, [], [], [
            'PHP_AUTH_USER' => 'eric',
            'PHP_AUTH_PW' => 'eric',
        ]);
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
