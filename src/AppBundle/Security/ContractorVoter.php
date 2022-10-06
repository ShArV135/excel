<?php

namespace AppBundle\Security;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class ContractorVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    private $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy)
    {
        $this->roleHierarchy = $roleHierarchy;
    }

    protected function supports($attribute, $subject): bool
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
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Contractor) {
            return false;
        }

        if ($attribute === self::DELETE) {
            return $this->isGranted($user, 'ROLE_MANAGER');
        }

        if (in_array('ROLE_CUSTOMER_MANAGER', $user->getRoles(), true)) {
            return $user === $subject->getManager();
        }

        if (in_array('ROLE_PROVIDER_MANAGER', $user->getRoles(), true)) {
            return $subject->getType() === Contractor::PROVIDER;
        }

        return true;
    }

    private function isGranted(User $user, string $roleCheck): bool
    {
        $roles = array_map(static function(string $role) {
            return new Role($role);
        }, $user->getRoles());
        $roles = $this->roleHierarchy->getReachableRoles($roles);

        foreach ($roles as $role) {
            if ($role->getRole() === $roleCheck) {
                return true;
            }
        }

        return false;
    }
}
