<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Exception\NotImplementedException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if ($id = $request->get('id')) {
            $timetable = $em->find('AppBundle:Timetable', $id);
        } else {
            $timetable = $em->getRepository('AppBundle:Timetable')->getCurrent();
        }

        $fixedColumns = [
            'manager',
            'customer',
            'provider',
            'object',
            'mechanism',
            'comment',
            'price_for_customer',
            'price_for_provider',
            'sum_times',
        ];

        switch (true) {
            case $this->isGranted('ROLE_CUSTOMER_MANAGER'):
                $show = 'customer_manager';
                break;
            case $this->isGranted('ROLE_DISPATCHER'):
                $show = 'dispatcher';
                break;
            case $this->isGranted('ROLE_PROVIDER_MANAGER'):
                $show = 'provider_manager';
                break;
            case $this->isGranted('ROLE_GENERAL_MANAGER'):
                $show = $request->get('show', 'general_manager');
                break;
            default:
                throw new NotImplementedException('Not implemented.');
                break;
        }

        switch ($show) {
            case 'customer_manager':
                $columns = [
                    'manager',
                    'customer',
                    'object',
                    'mechanism',
                    'comment',
                    'price_for_customer',
                    'sum_times',
                    'times',
                    'customer_salary',
                    'customer_paid',
                    'customer_balance',
                ];
                break;
            case 'dispatcher':
                $columns = [
                    'manager',
                    'customer',
                    'provider',
                    'object',
                    'mechanism',
                    'comment',
                    'price_for_customer',
                    'sum_times',
                    'times',
                    'customer_salary',
                ];
                break;
            case 'provider_manager':
                $columns = [
                    'manager',
                    'object',
                    'provider',
                    'mechanism',
                    'customer',
                    'comment',
                    'price_for_provider',
                    'sum_times',
                    'times',
                    'provider_salary',
                    'provider_paid',
                    'provider_balance',
                    'customer_balance',
                ];
                break;
            case 'general_manager':
                $columns = [
                    'manager',
                    'customer',
                    'object',
                    'mechanism',
                    'comment',
                    'price_for_customer',
                    'sum_times',
                    'times',
                    'customer_salary',
                    'customer_paid',
                    'customer_balance',
                    'margin_sum',
                    'margin_percent',
                ];
                break;
            default:
                $columns = [
                    'manager',
                    'customer',
                    'provider',
                    'object',
                    'mechanism',
                    'comment',
                    'price_for_customer',
                    'price_for_provider',
                    'sum_times',
                    'times',
                    'customer_salary',
                    'provider_salary',
                    'customer_paid',
                    'customer_balance',
                    'provider_paid',
                    'provider_balance',
                    'margin_sum',
                    'margin_percent',
                ];
                break;
        }

        $numOfFixed = count(array_intersect($columns, $fixedColumns));

        $qb = $em->getRepository('AppBundle:User')->createQueryBuilder('user');
        $qb = $qb
            ->where($qb->expr()->like('user.roles', ':roles'))
            ->setParameter('roles', '%ROLE_CUSTOMER_MANAGER%')
        ;

        if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $qb
            ->andWhere($qb->expr()->eq('user.id', ':id'))
            ->setParameter('id', $this->getUser())
        ;
        }
        $managers = $qb
            ->getQuery()
            ->getResult()
        ;

        $managersByFio = [];
        /** @var User $manager */
        foreach ($managers as $manager) {
            $firstname = $manager->getFirstName() ?: '';
            $lastname = $manager->getLastname() ?: '';
            $surname = $manager->getSurname() ?: '';

            $key = mb_substr($lastname, 0, 1).mb_substr($firstname, 0, 1).mb_substr($surname, 0, 1);
            $value = $manager->getFullName();

            $managersByFio[$key] = $value;
        }

        return $this->render(
            '@App/default/index.html.twig',
            [
                'timetable' => $timetable,
                'columns' => $columns,
                'num_of_fixed' => $numOfFixed,
                'fixed_columns' => $fixedColumns,
                'managers_by_fio' => $managersByFio,
                'view_mode' => $show,
            ]
        );
    }
}
