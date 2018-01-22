<?php

namespace AppBundle\Menu;

use AppBundle\Entity\Timetable;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param FactoryInterface $factory
     * @param array            $options
     * @return \Knp\Menu\ItemInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $timetableRepository = $em->getRepository('AppBundle:Timetable');

        $menu = $factory->createItem(
            'root',
            [
                'childrenAttributes' => [
                    'class' => 'nav navbar-nav',
                ],
            ]
        );

        $menu->addChild('Табель', ['route' => 'homepage']);
        $menu->addChild('Контрагенты', ['route' => 'contractor_list']);

        $archive = $menu->addChild(
            'Архив',
            [
                'uri' => '#',
                'linkAttributes' => [
                    'data-toggle' => 'dropdown',
                ],
                'childrenAttributes' => [
                    'class' => 'dropdown-menu',
                ],
            ]
        );
        $lastTimetable = $timetableRepository->getLastOrCreateTable();

        /** @var Timetable $timetable */
        foreach ($timetableRepository->getAllPrevious($lastTimetable, false) as $timetable) {
            $archive->addChild(
                $timetable->getName(),
                [
                    'route' => 'homepage',
                    'routeParameters' => [
                        'id' => $timetable->getId(),
                    ],
                ]
            );
        }
        $archive->addChild('', ['attributes' => ['role' => 'separator', 'class' => 'divider']]);
        $archive->addChild('* Создать новый', ['route' => 'timetable_create']);

        return $menu;
    }
}
