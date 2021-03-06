<?php

namespace AppBundle\Security;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ContractorVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, array(self::VIEW, self::EDIT, self::DELETE))) {
            return false;
        }

        if (!$subject instanceof Contractor) {
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

        if (!$subject instanceof Contractor) {
            return false;
        }

        if ($attribute === self::VIEW) {
            if (in_array('ROLE_CUSTOMER_MANAGER', $user->getRoles())) {
                return $user === $subject->getManager();
            }

            if (in_array('ROLE_PROVIDER_MANAGER', $user->getRoles())) {
                return $subject->getType() === Contractor::PROVIDER;
            }

            return true;
        }

        return in_array('ROLE_MANAGER', $user->getRoles())
            || in_array('ROLE_GENERAL_MANAGER', $user->getRoles());
    }
}
