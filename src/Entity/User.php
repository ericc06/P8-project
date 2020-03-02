<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("user")
 * @ORM\Entity
 * @UniqueEntity("username")
 * //@UniqueEntity("email")
 * @UniqueEntity(
 *     fields={"email"},
 *     message="email.exists",
 *     groups={"creation","update"}
 * )
 */
class User implements UserInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     * @Assert\NotBlank(message="username.required")
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64)
     * @Assert\NotBlank(groups={"creation"})
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     * @Assert\NotBlank(message="Vous devez saisir une adresse email.")
     * @Assert\Email(message="email.format.incorrect")
     */
    private $email;

    /**
     * @ORM\Column(name="roles", type="array")
     */
    private $roles = [];

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Task",
     *     mappedBy="user",
     *     cascade={"persist", "remove"}
     * )
     * @Assert\Valid()
     */
    private $tasks;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getSalt()
    {
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getRoles(): array
    {
        // See https://symfony.com/doc/3.4/security.html
        // and https://symfonycasts.com/screencast/symfony-security/dynamic-roles
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles)
    {
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }
        foreach ($roles as $role) {
            if ('ROLE_' !== substr($role, 0, 5)) {
                throw new InvalidArgumentException("Chaque rôle doit commencer par 'ROLE_'");
            }
        }
        $this->roles = $roles;

        return $this;
    }

    /**
     * Add task.
     *
     * @return User
     */
    public function addTask(Task $task): self
    {
        $this->tasks[] = $task;
        $task->setUser($this);

        return $this;
    }

    /**
     * Remove task.
     */
    public function removeTask(Task $task)
    {
        $this->tasks->removeElement($task);
    }

    /**
     * Get task.
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    /**
     * Set task.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function setTasks(ArrayCollection $tasks): self
    {
        $this->tasks = $tasks;

        return $this;
    }

    public function eraseCredentials()
    {
    }
}
