<?php

namespace AppBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class TimetableExtension extends \Twig_Extension
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array|\Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_timetables', [$this, 'getTimetables']),
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getTimetables()
    {
        $repository = $this->container->get('doctrine.orm.entity_manager')->getRepository('AppBundle:Timetable');

        $current = $repository->getCurrent();

        return [
            'list' => $repository->findAll(),
            'current' => $current,
        ];

    }
}
