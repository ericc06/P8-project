<?php

namespace App\Security;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class TaskVoter extends Voter
{
    const VIEW = 'view';
    const CREATE = 'create';
    const EDIT = 'edit';
    const TOGGLE = 'toggle';
    const DELETE = 'delete';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::TOGGLE, self::DELETE])) {
            return false;
        }

        // only vote on Task objects inside this voter
        if (!$subject instanceof Task) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        // ROLE_ADMIN can do anything!
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // we know $subject is a Task object, thanks to supports
        /** @var Task $task */
        $task = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($task, $user);
            case self::CREATE:
                return $this->canCreate($task, $user);
            case self::EDIT:
                return $this->canEdit($task, $user);
            case self::TOGGLE:
                return $this->canToggle($task, $user);
            case self::DELETE:
                return $this->canDelete($task, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView()
    {
        // if they can edit, they can view
        //if ($this->canEdit($task, $user)) {
        //    return true;
        //}

        // If the user is logged in (which was already ckecked previously in voteOnAttribute(...))
        // he/she can view all tasks.
        return true;
    }

    private function canCreate()
    {
        // If the user is logged in (which was already ckecked previously in voteOnAttribute(...))
        // he/she can create a task.
        return true;
    }

    private function canEdit()
    {
        // If the user is logged in (which was already ckecked previously in voteOnAttribute(...))
        // he/she can edit all tasks.
        return true;
    }

    private function canToggle()
    {
        // If the user is logged in (which was already ckecked previously in voteOnAttribute(...))
        // he/she can toggle all tasks.
        return true;
    }

    private function canDelete(Task $task, User $user)
    {
        // Only the user who created the task (and the administrators, which was ckecked previously
        // in voteOnAttribute(...)) can delete it.
        return $user === $task->getUser();
    }
}
