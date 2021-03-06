<?php

namespace AppBundle\Twig;

use AppBundle\Service\Utils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\TwigFilter;

class TimetableExtension extends \Twig_Extension
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('get_timetables', [$this, 'getTimetables']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('month', [$this, 'getMonth']),
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getTimetables()
    {
        $repository = $this->container->get('doctrine.orm.entity_manager')->getRepository('AppBundle:Timetable');
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ($request->get('_route') === 'homepage' && ($id = $request->get('id'))) {
            $current = $repository->find($id);
        } else {
            $current = $repository->getCurrent();
        }


        return [
            'list' => $repository->findBy([], ['created' => 'DESC']),
            'current' => $current,
        ];

    }

    public function getMonth(\DateTime $dateTime): string
    {
        return Utils::getMonth($dateTime);
    }
}
