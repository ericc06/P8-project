<?php

// tests/Controller/TaskControllerTest.php

namespace App\Tests\Controller;

use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TaskControllerTest extends WebTestCase
{
    private $client = null;
    private $manager;

    protected function setUp()
    {
        $this->client = static::createClient();
        //$client->followRedirects();

        $kernel = self::bootKernel();

        $this->manager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testListUndoneTasksNotLoggedIn()
    {
        $this->client->request('GET', '/tasks');

        // When requesting the "/tasks" URL not being logged in,
        // we should be redirected (with status code 302) to the login form page.
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/login$/', $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testListUndoneTasksAsAdmin()
    {
        $this->logInAsAdmin();
        $this->client->request('GET', '/tasks');

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertContains(
            'Créer une nouvelle tâche',
            $this->client->getResponse()->getContent()
        );
        $this->assertNotContains(
            'Consulter la liste des tâches à faire',
            $this->client->getResponse()->getContent()
        );
        $this->assertContains(
            'Consulter la liste des tâches terminées',
            $this->client->getResponse()->getContent()
        );
        $this->assertSelectorTextContains('h4 > a', 'Tâche anonyme n°1');
    }

    public function testListUndoneTasksAsUser()
    {
        $this->logInAsUser();
        $this->client->request('GET', '/tasks');

        $this->assertResponseIsSuccessful();
        $this->assertContains(
            'Créer une nouvelle tâche',
            $this->client->getResponse()->getContent()
        );
        $this->assertNotContains(
            'Consulter la liste des tâches à faire',
            $this->client->getResponse()->getContent()
        );
        $this->assertContains(
            'Consulter la liste des tâches terminées',
            $this->client->getResponse()->getContent()
        );
        $this->assertSelectorTextContains('h4 > a', 'Tâche anonyme n°1');
    }

    public function testListDoneTasksNotLoggedIn()
    {
        $this->client->request('GET', '/tasks/done');

        // When requesting the "/tasks/done" URL not being logged in,
        // we should be redirected (with status code 302) to the login form page.
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/login$/', $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testListDoneTasksAsAdmin()
    {
        $this->logInAsAdmin();
        $this->client->request('GET', '/tasks/done');

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertContains(
            'Créer une nouvelle tâche',
            $this->client->getResponse()->getContent()
        );
        $this->assertContains(
            'Consulter la liste des tâches à faire',
            $this->client->getResponse()->getContent()
        );
        $this->assertNotContains(
            'Consulter la liste des tâches terminées',
            $this->client->getResponse()->getContent()
        );
        $this->assertSelectorTextContains('h4 > a', 'Tâche anonyme n°2');
    }

    public function testListDoneTasksAsUser()
    {
        $this->logInAsUser();
        $this->client->request('GET', '/tasks/done');

        $this->assertResponseIsSuccessful();
        $this->assertContains(
            'Créer une nouvelle tâche',
            $this->client->getResponse()->getContent()
        );
        $this->assertContains(
            'Consulter la liste des tâches à faire',
            $this->client->getResponse()->getContent()
        );
        $this->assertNotContains(
            'Consulter la liste des tâches terminées',
            $this->client->getResponse()->getContent()
        );
        $this->assertSelectorTextContains('h4 > a', 'Tâche anonyme n°2');
    }

    public function testCreateTaskAccessNotLoggedIn()
    {
        $this->client->request('GET', '/tasks/create');

        // When requesting the "/tasks/create" URL not being logged in,
        // we should be redirected (with status code 302) to the login form page.
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/login$/', $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testCreateTaskAccessAsAdmin()
    {
        $this->logInAsAdmin();
        $this->client->request('GET', '/tasks/create');

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('label', 'Titre');
    }

    public function testCreateTaskAccessAsUser()
    {
        $this->logInAsUser();
        $this->client->request('GET', '/tasks/create');

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('label', 'Titre');
    }

    public function testCreateTaskSubmitAsAdmin()
    {
        //$this->logInAsAdmin();
        //$crawler = $this->client->request('GET', '/tasks/create');
        $this->client->request('GET', '/tasks/create', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $this->client->submitForm('Ajouter', [
            'task[title]' => 'My admin task',
            'task[content]' => 'This is the task content.',
        ]);

        // For CSRF token, read:
        // https://stackoverflow.com/questions/24481042/get-the-csrf-token-in-a-test-csrf-token-is-invalid-functional-ajax-test
        //
        // Finally, CSRF protection has been disabled in test mode
        // in config\packages\test\framework.yaml.
        /*
        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]'] = 'My admin task';
        $form['task[content]'] = 'This is the task content.';
        */
        // Create our CSRF token - with $intention = `post_type`
        //$csrfToken = $this->client->getContainer()->get('security.csrf.token_manager')->getToken('task_type');
        //$form['task[_token]'] = $csrfToken; // Add it to your `csrf_field_name`
        //$this->client->submit($form);

        $crawler = $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertRegExp('/\/tasks$/', $crawler->getUri());
        // Can't use assertSelectorTextContains() because it only checks the first selector occurrence
        // and the newly added task is the last one in the displayed list of tasks.
        //$this->assertSelectorTextContains('h4 > a', 'My admin task');
        $this->assertRegExp(
            '/<a href="\/tasks\/[0-9]+\/edit">My admin task<\/a>/',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCreateTaskSubmitAsUser()
    {
        $this->client->request('GET', '/tasks/create', [], [], [
            'PHP_AUTH_USER' => 'eric',
            'PHP_AUTH_PW' => 'eric',
        ]);

        $this->client->submitForm('Ajouter', [
            'task[title]' => 'My user task',
            'task[content]' => 'This is the task content.',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertRegExp('/\/tasks$/', $crawler->getUri());
        // Can't use assertSelectorTextContains() because it only checks the first selector occurrence
        // and the newly added task is the last one in the displayed list of tasks.
        //$this->assertSelectorTextContains('h4 > a', 'My admin task');
        $this->assertRegExp(
            '/<a href="\/tasks\/[0-9]+\/edit">My user task<\/a>/',
            $this->client->getResponse()->getContent()
        );
    }

    public function testEditTaskAccessNotLoggedIn()
    {
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°1');

        $this->client->request('GET', '/tasks/'.$task_id.'/edit');

        // When requesting the task edit URL not being logged in,
        // we should be redirected (with status code 302) to the login form page.
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/login$/', $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testEditTaskAccessAsAdmin()
    {
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°1');

        $this->client->request('GET', '/tasks/'.$task_id.'/edit', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('button', 'Modifier');
        // Don't know how to check the "value" attribute of an input field.
        $this->assertRegExp(
            '/value="Tâche anonyme n°1"/',
            $this->client->getResponse()->getContent()
        );
    }

    public function testEditTaskAccessAsUser()
    {
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°1');

        $this->client->request('GET', '/tasks/'.$task_id.'/edit', [], [], [
            'PHP_AUTH_USER' => 'eric',
            'PHP_AUTH_PW' => 'eric',
        ]);

        //$this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('button', 'Modifier');
        $this->assertRegExp(
            '/value="Tâche anonyme n°1"/',
            $this->client->getResponse()->getContent()
        );
    }

    public function testEditTaskSubmitAsAdmin()
    {
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°1');

        $this->client->request('GET', '/tasks/'.$task_id.'/edit', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $crawler = $this->client->submitForm('Modifier', [
            'task[title]' => 'Tâche anonyme n°1 modif',
            'task[content]' => 'This is the task modified content.',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertRegExp('/tasks$/', $crawler->getUri());
        $this->assertSelectorTextContains('h4 > a', 'Tâche anonyme n°1 modif');
    }

    public function testEditTaskSubmitAsUser()
    {
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°1');

        $this->client->request('GET', '/tasks/'.$task_id.'/edit', [], [], [
            'PHP_AUTH_USER' => 'eric',
            'PHP_AUTH_PW' => 'eric',
        ]);

        $crawler = $this->client->submitForm('Modifier', [
            'task[title]' => 'Tâche anonyme n°1 modif',
            'task[content]' => 'This is the task modified content.',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertRegExp('/tasks$/', $crawler->getUri());
        $this->assertSelectorTextContains('h4 > a', 'Tâche anonyme n°1 modif');
    }

    public function testToggleUndoneTaskDoneNotLoggedIn()
    {
        // Because this application interface is not accessible when not logged in
        // we just try to directly call the route.
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°1');

        //$this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/tasks/'.$task_id.'/toggle/task_list');

        // When requesting this URL not being logged in,
        // we should be redirected (with status code 302) to the login form page.
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/login$/', $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testToggleUndoneTaskDoneAsAdmin()
    {
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°1');

        $crawler = $this->client->request('GET', '/tasks', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        // We first check that the task to be toggled "done" exists in the undone tasks list page
        $this->assertSelectorTextContains('h4 > a', 'Tâche anonyme n°1');

        // Then we toggle it "done"
        $form = $crawler
            ->filter('div.thumbnail-buttons form[action="/tasks/'.$task_id.'/toggle/task_list"]')
            ->form();

        $this->client->submit($form);

        $crawler = $this->client->followRedirect();

        $this->assertRegExp('/tasks$/', $crawler->getUri());
        $this->assertContains(
            'La tâche &quot;Tâche anonyme n°1&quot; a bien été marquée comme faite.',
            $this->client->getResponse()->getContent()
        );
        $this->assertSelectorTextNotContains('h4 > a', 'Tâche anonyme n°1');
    }

    public function testToggleUndoneTaskDoneAsUser()
    {
        $task_id = self::getTaskIdByTitle('Tâche Eric n°1');

        $crawler = $this->client->request('GET', '/tasks', [], [], [
            'PHP_AUTH_USER' => 'eric',
            'PHP_AUTH_PW' => 'eric',
        ]);

        // We first check that the task to be toggled "done" exists in the undone tasks list page

        // Can't use assertSelectorTextContains() because it only checks the first selector occurrence
        // and the task we are looking for is not the first one in the displayed list of tasks.
        // See: https://github.com/symfony/symfony-docs/issues/13036
        //$this->assertSelectorTextContains('h4 > a', 'Tâche Eric n°1');
        $this->assertGreaterThan(0, $crawler->filter('h4 > a:contains("Tâche Eric n°1")')->count());

        // Then we toggle it "done"
        $form = $crawler
            ->filter('div.thumbnail-buttons form[action="/tasks/'.$task_id.'/toggle/task_list"]')
            ->form();

        $this->client->submit($form);

        $crawler = $this->client->followRedirect();

        $this->assertRegExp('/tasks$/', $crawler->getUri());
        $this->assertContains(
            'La tâche &quot;Tâche Eric n°1&quot; a bien été marquée comme faite.',
            $this->client->getResponse()->getContent()
        );
        $this->assertSelectorTextNotContains('h4 > a', 'Tâche Eric n°1');
    }

    public function testToggleDoneTaskUndoneNotLoggedIn()
    {
        // Because this application interface is not accessible when not logged in
        // we just try to directly call the route instead of submitting the toggle form.
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°2');

        //$this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/tasks/'.$task_id.'/toggle/task_list');

        // When requesting this URL not being logged in,
        // we should be redirected (with status code 302) to the login form page.
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/\/login$/', $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testToggleDoneTaskUndoneAsAdmin()
    {
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°2');

        $crawler = $this->client->request('GET', '/tasks/done', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        // We first check that the task to be toggled "undone" exists in the done tasks list page
        $this->assertSelectorTextContains('h4 > a', 'Tâche anonyme n°2');

        // Then we toggle it "undone"
        $form = $crawler
            ->filter('div.thumbnail-buttons form[action="/tasks/'.$task_id.'/toggle/task_done_list"]')
            ->form();

        $this->client->submit($form);

        $crawler = $this->client->followRedirect();

        $this->assertRegExp('/\/tasks\/done$/', $crawler->getUri());
        $this->assertContains(
            'La tâche &quot;Tâche anonyme n°2&quot; a bien été marquée comme non faite.',
            $this->client->getResponse()->getContent()
        );
        $this->assertSelectorTextNotContains('h4 > a', 'Tâche anonyme n°2');
    }

    public function testToggleDoneTaskUndoneAsUser()
    {
        $task_id = self::getTaskIdByTitle('Tâche Eric n°5');

        $crawler = $this->client->request('GET', '/tasks/done', [], [], [
            'PHP_AUTH_USER' => 'eric',
            'PHP_AUTH_PW' => 'eric',
        ]);

        // We first check that the task to be toggled "undone" exists in the done tasks list page

        // Can't use assertSelectorTextContains() because it only checks the first selector occurrence
        // and the task we are looking for is not the first one in the displayed list of tasks.
        // See: https://github.com/symfony/symfony-docs/issues/13036
        //$this->assertSelectorTextContains('h4 > a', 'Tâche Eric n°5');
        $this->assertGreaterThan(0, $crawler->filter('h4 > a:contains("Tâche Eric n°5")')->count());

        // Then we toggle it "undone"
        $form = $crawler
            ->filter('div.thumbnail-buttons form[action="/tasks/'.$task_id.'/toggle/task_done_list"]')
            ->form();

        $this->client->submit($form);

        $crawler = $this->client->followRedirect();

        $this->assertRegExp('/\/tasks\/done$/', $crawler->getUri());
        $this->assertContains(
            'La tâche &quot;Tâche Eric n°5&quot; a bien été marquée comme non faite.',
            $this->client->getResponse()->getContent()
        );
        $this->assertSelectorTextNotContains('h4 > a', 'Tâche Eric n°5');
    }

    public function testAllDeleteTaskButtonsArePresentForAdmin()
    {
        $crawler = $this->client->request('GET', '/tasks', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        // Counting the number of forms having action like /tasks/143/delete
        $count = preg_match_all(
            '/action="\/tasks\/[0-9]+\/delete"/',
            $this->client->getResponse()->getContent()
        );

        $this->assertEquals(13, $count);
    }

    public function testOnlyOwnersDeleteTaskButtonsArePresentForUser()
    {
        $crawler = $this->client->request('GET', '/tasks', [], [], [
            'PHP_AUTH_USER' => 'eric',
            'PHP_AUTH_PW' => 'eric',
        ]);

        // Counting the number of forms having action like /tasks/143/delete
        $count = preg_match_all(
            '/action="\/tasks\/[0-9]+\/delete"/',
            $this->client->getResponse()->getContent()
        );

        $this->assertEquals(4, $count);
    }

    // Because clicking a "Delete" task button opens a modal popup PNPUnit can't
    // interact with, we only test the delete task method by directly calling its route.
    public function testDeleteTaskSubmitNotLoggedIn()
    {
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°1');

        $this->client->request('GET', '/tasks/'.$task_id.'/delete');

        // When requesting this URL not being logged in,
        // we should be redirected (with status code 302) to the login form page.
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/login$/', $this->client->getResponse()->headers->get('Location'));

        $this->client->followRedirect();

        $this->assertSelectorTextContains('.navbar-brand', 'To Do List app');
    }

    public function testDeleteTaskSubmitAsAdmin()
    {
        // We first check that the task to be deleted exists in the task list page
        $this->client->request('GET', '/tasks', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $this->assertSelectorTextContains('h4 > a', 'Tâche anonyme n°1');

        // Then we delete it
        $task_id = self::getTaskIdByTitle('Tâche anonyme n°1');

        $crawler = $this->client->request('GET', '/tasks/'.$task_id.'/delete', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertRegExp('/tasks$/', $crawler->getUri());
        $this->assertContains(
            'La tâche a bien été supprimée.',
            $this->client->getResponse()->getContent()
        );
        $this->assertSelectorTextNotContains('h4 > a', 'Tâche anonyme n°1');
    }

    public function testDeleteTaskSubmitAsUser()
    {
        // We first check that the task to be deleted exists in the task list page
        $crawler = $this->client->request('GET', '/tasks', [], [], [
            'PHP_AUTH_USER' => 'eric',
            'PHP_AUTH_PW' => 'eric',
        ]);

        // Can't use assertSelectorTextContains() because it only checks the first selector occurrence
        // and the task we are looking for is not the first one in the displayed list of tasks.
        // See: https://github.com/symfony/symfony-docs/issues/13036
        //$this->assertSelectorTextContains('h4 > a', 'Tâche Eric n°1');
        $this->assertGreaterThan(0, $crawler->filter('h4 > a:contains("Tâche Eric n°1")')->count());

        // Then we delete it
        $task_id = self::getTaskIdByTitle('Tâche Eric n°1');

        $crawler = $this->client->request('GET', '/tasks/'.$task_id.'/delete', [], [], [
            'PHP_AUTH_USER' => 'eric',
            'PHP_AUTH_PW' => 'eric',
        ]);

        $crawler = $this->client->followRedirect();

        $this->assertRegExp('/tasks$/', $crawler->getUri());
        $this->assertContains(
            'La tâche a bien été supprimée.',
            $this->client->getResponse()->getContent()
        );
        $this->assertSelectorTextNotContains('h4 > a', 'Tâche Eric n°1');
    }

    // Currently not used
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

    // Currently not used
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

    private function getTaskIdByTitle(string $title)
    {
        return $this->manager
            ->getRepository(Task::class)
            ->findBy(['title' => $title])[0]
            ->getId();
    }

    protected function tearDown()
    {
        parent::tearDown();

        // avoid memory leaks
        $this->client = null;
        $this->crawler = null;
        $this->session = null;
        $this->token = null;
        $this->cookie = null;
        $this->manager->close();
        $this->manager = null;
    }
}
