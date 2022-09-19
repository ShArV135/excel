<?php

namespace AppBundle\Command;

use AppBundle\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class CreateUserCommand
 */
class CreateUserCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('custom:create-user')
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addArgument('role', InputArgument::REQUIRED, 'Role')
            ->addArgument('firstname', InputArgument::REQUIRED, 'First Name')
            ->addArgument('lastname', InputArgument::REQUIRED, 'Last Name')
            ->addArgument('surname', InputArgument::REQUIRED, 'Sur Name')

        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $user = new User();
        $user->setUsername($input->getArgument('username'));
        $user->setRoles([$input->getArgument('role')]);
        $user->setFirstname($input->getArgument('firstname'));
        $user->setLastname($input->getArgument('lastname'));
        $user->setSurname($input->getArgument('surname'));

        $encoder = $this->container->get('security.password_encoder');
        $user->setPassword($encoder->encodePassword($user, $input->getArgument('password')));

        $this->container->get('doctrine.orm.entity_manager')->persist($user);
        $this->container->get('doctrine.orm.entity_manager')->flush();
    }
}