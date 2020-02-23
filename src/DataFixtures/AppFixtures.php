<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    public const USER_1 = 'user-1';
    public const USER_2 = 'user-2';
    public const USER_3 = 'user-3';

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
        $user->setRoles(array('ROLE_ADMIN'));
        $manager->persist($user);

        // Creating the anonymous user
        $user = new User();
            
        $user->setUsername('anonymous@system.user');
        $user->setPassword('ano-pwd');
        $user->setEmail('anonymous@system.user');
        $user->setRoles(array('ROLE_USER'));

        $manager->persist($user);

        for ($t = 1; $t < 4; $t++) {
            $task = new Task($user);

            $task->setCreatedAt(new \Datetime());
            $task->setTitle('Tâche anonyme n°' . $t);
            $task->setContent('Ceci est une tâche rattachée à l\'utilisateur anonyme.');

            $manager->persist($task);
        }

        // Creation of 3 users with the related resellers (FOSUser users)
        $usernames = ['Eric', 'Carine', 'Marc'];

        for ($i = 0; $i < 3; $i++) {
            $user = new User();
            
            $user->setUsername($usernames[$i]);
            $password = $this->encoder->encodePassword($user, strtolower($usernames[$i]));
            $user->setPassword($password);
            $user->setEmail($usernames[$i] . "@mail.loc");
            $user->setRoles(array('ROLE_USER'));

            $manager->persist($user);
            //$manager->flush();

            for ($t = 1; $t < 6; $t++) {
                $task = new Task($user);

                $task->setCreatedAt(new \Datetime());
                $task->setTitle('Tâche '. $usernames[$i] . ' n°' . $t);
                $task->setContent('Ceci est la tâche n°' . $t . '. Elle a été créée par ' . $usernames[$i]. '.');

                $manager->persist($task);
            }

            $manager->flush();
        }
    }
}
