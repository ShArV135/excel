<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\TimetableRowTimes;
use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Exception\NotImplementedException;

class TimetableController extends Controller
{
    /**
     * @Route("/timetable-create", name="timetable_create")
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createAction()
    {
        $em = $this->getDoctrine()->getManager();
        $em->getRepository('AppBundle:Timetable')->create();

        return $this->redirectToRoute('homepage');
    }

    /**
     * @param Timetable $timetable
     * @param Request   $request
     * @Route("/timetable-data/{timetable}", name="timetable_data")
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getDataAction(Timetable $timetable, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $criteria = [];

        if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $criteria['manager'] = $this->getUser();
        }

        $timetableRows = $em->getRepository('AppBundle:TimetableRow')->findBy($criteria, ['customer' => 'ASC']);

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

        $managersById = [];
        $managersByFio = [];
        /** @var User $manager */
        foreach ($managers as $manager) {
            $firstname = $manager->getFirstName() ?: '';
            $lastname = $manager->getLastname() ?: '';
            $surname = $manager->getSurname() ?: '';

            $key = mb_substr($lastname, 0, 1).mb_substr($firstname, 0, 1).mb_substr($surname, 0, 1);
            $value = implode([$lastname, $firstname, $surname], ' ');

            $managersById[$manager->getId()] = $key;
            $managersByFio[$key] = $value;
        }

        $rows = [];
        /** @var TimetableRow $timetableRow */
        foreach ($timetableRows as $timetableRow) {
            $manager = $timetableRow->getManager();
            $customer = $timetableRow->getCustomer();
            $provider = $timetableRow->getProvider();

            $row = [
                'id' => $timetableRow->getId(),
                'customer_id' => $customer->getId(),
            ];

            if ($provider) {
                $row['provider_id'] = $provider->getId();
            }

            $row['controls'] = [
                'update' => $this->generateUrl('timetable_row_update', ['timetableRow' => $timetableRow->getId()]),
                'delete' => $this->generateUrl('timetable_row_delete', ['timetableRow' => $timetableRow->getId()]),
            ];

            list(
                $timetableRowTimes,
                $sumTimes,
                $customerSalary,
                $providerSalary,
                $customerBalance,
                $providerBalance,
                $marginSum,
                $marginPercent,
                $customerPaid,
                $providerPaid,
                ) = array_values($this->get('timetable.helper')->calculateRowData($timetable, $timetableRow));

            foreach ($columns as $column) {
                switch ($column) {
                    case 'manager':
                        $value = $managersById[$manager->getId()];
                        break;
                    case 'customer':
                        $value = [
                            'url' => $this->generateUrl('contractor_view', ['contractor' => $customer->getId()]),
                            'name' => $customer->getName(),
                        ];
                        break;
                    case 'provider':
                        if ($provider) {
                            $value = [
                                'url' => $this->generateUrl('contractor_view', ['contractor' => $provider->getId()]),
                                'name' => $provider->getName(),
                            ];
                        } else {
                            $value = null;
                        }
                        break;
                    case 'object':
                        $value = $timetableRow->getObject();
                        break;
                    case 'mechanism':
                        $value = $timetableRow->getMechanism();
                        break;
                    case 'comment':
                        $value = $timetableRow->getComment();
                        break;
                    case 'price_for_customer':
                        $value = $timetableRow->getPriceForCustomer();
                        break;
                    case 'price_for_provider':
                        $value = $timetableRow->getPriceForProvider();
                        break;
                    case 'sum_times':
                        $value = $sumTimes;
                        break;
                    case 'times':
                        /** @var TimetableRowTimes $timetableRowTimes */
                        $colors = $timetableRowTimes->getColors();
                        $comments = $timetableRowTimes->getComments();
                        foreach ($timetableRowTimes->getTimes() as $day => $time) {
                            $row['times_'.$day] = [
                                'id' => $timetableRowTimes->getId(),
                                'day' => $day,
                                'comment' => $comments[$day],
                                'comment_url' => $this->generateUrl(
                                    'timetable_row_times_update_comment',
                                    [
                                        'timetableRowTimes' => $timetableRowTimes->getId(),
                                    ]
                                ),
                                'time' => $time,
                            ];
                            $row['_times_'.$day.'_class'] = 'times '.$colors[$day];
                            $row['_times_'.$day.'_data'] = [
                                'id' => $timetableRowTimes->getId(),
                                'day' => $day,
                            ];
                        }
                        $value = $timetableRowTimes;
                        break;
                    case 'customer_salary':
                        $value = $customerSalary;
                        break;
                    case 'provider_salary':
                        $value = $providerSalary;
                        break;
                    case 'customer_paid':
                        $value = $customerPaid;
                        break;
                    case 'provider_paid':
                        $value = $providerPaid;
                        break;
                    case 'customer_balance':
                        $value = $customerBalance;

                        if ($customerBalance < 0) {
                            $row['_customer_balance_class'] = 'customer_balance bg-right text-white';
                        }
                        break;
                    case 'provider_balance':
                        $value = $providerBalance;

                        if ($providerBalance < 0) {
                            $row['_provider_balance_class'] = 'provider_balance bg-pink';
                        }
                        break;
                    case 'margin_sum':
                        $value = $marginSum;
                        break;
                    case 'margin_percent':
                        $value = $marginPercent;
                        break;
                    default:
                        $value = '';
                }

                $row[$column] = $value;
            }

            $rows[] = $row;
        }

        return new JsonResponse($rows);
    }
}
