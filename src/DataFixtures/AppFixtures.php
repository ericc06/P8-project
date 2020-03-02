<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        // Creating the admin user
        $user = new User();

        $user->setUsername('admin');
        $password = $this->encoder->encodePassword($user, 'admin');
        $user->setPassword($password);
        $user->setEmail('admin@system.user');
        $user->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);

        // Creating the anonymous user
        $user = new User();

        $user->setUsername('anonymous@system.user');
        $user->setPassword('ano-pwd');
        $user->setEmail('anonymous@system.user');
        $user->setRoles(['ROLE_USER']);

        $manager->persist($user);

        // Creating 3 tasks for the anonymous user among which 2 "done" tasks.
        for ($t = 1; $t < 4; ++$t) {
            $task = new Task($user);

            $task->setTitle('Tâche anonyme n°'.$t);
            $task->setContent('Ceci est une tâche rattachée à l\'utilisateur anonyme.');

            if ($t > 1) {
                $task->toggle(true);
            }

            $manager->persist($task);
        }

        // Creation of 3 users with the related resellers (FOSUser users)
        $usernames = ['Eric', 'Carine', 'Marc'];

        for ($i = 0; $i < 3; ++$i) {
            $user = new User();

            $user->setUsername($usernames[$i]);
            $password = $this->encoder->encodePassword($user, strtolower($usernames[$i]));
            $user->setPassword($password);
            $user->setEmail($usernames[$i].'@mail.loc');
            $user->setRoles(['ROLE_USER']);

            $manager->persist($user);
            //$manager->flush();

            // Creating 5 tasks for each user, setting the last one as done.
            for ($t = 1; $t < 6; ++$t) {
                $task = new Task($user);

                $task->setTitle('Tâche '.$usernames[$i].' n°'.$t);
                $task->setContent('Ceci est la tâche n°'.$t.'. Elle a été créée par '.$usernames[$i].'.');

                if (5 == $t) {
                    $task->toggle(true);
                }

                $manager->persist($task);
            }

            $manager->flush();
        }
    }
}
