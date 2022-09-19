<?php

namespace AppBundle\Security;

use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const VIEW_REPORT = 'view_report';

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, array(self::VIEW, self::EDIT, self::DELETE, self::VIEW_REPORT))) {
            return false;
        }

        if (!$subject instanceof User) {
            return false;
        }

        return true;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string         $attribute
     * @param mixed          $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof User) {
            return false;
        }

        if (in_array('ROLE_GENERAL_MANAGER', $user->getRoles())) {
            return true;
        }

        if ($attribute === self::VIEW_REPORT) {
            if ($subject->getId() === $user->getId()) {
                return true;
            }
        }

        if ($subject->getId() === $user->getId()) {
            return false;
        }

        $roles = $subject->getRoles();

        return !in_array('ROLE_GENERAL_MANAGER', $roles) && !in_array('ROLE_MANAGER', $roles);
    }
}
