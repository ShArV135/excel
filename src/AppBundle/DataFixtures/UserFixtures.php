<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $userData = [
            [
                'username' => 'customer_manager',
                'password' => 'customer_manager',
                'roles' => ['ROLE_CUSTOMER_MANAGER'],
            ],
            [
                'username' => 'provider_manager',
                'password' => 'provider_manager',
                'roles' => ['ROLE_PROVIDER_MANAGER'],
            ],
            [
                'username' => 'general_manager',
                'password' => 'general_manager',
                'roles' => ['ROLE_GENERAL_MANAGER'],
            ],
            [
                'username' => 'dispatcher',
                'password' => 'dispatcher',
                'roles' => ['ROLE_DISPATCHER'],
            ],
        ];

        foreach ($userData as $data) {
            $user = new User();
            $user->setUsername($data['username']);
            $user->setRoles($data['roles']);

            $password = $this->encoder->encodePassword($user, $data['password']);
            $user->setPassword($password);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
