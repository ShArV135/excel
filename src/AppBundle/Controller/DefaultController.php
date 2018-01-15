<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Payment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Intl\Exception\NotImplementedException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $timetable = $em->getRepository('AppBundle:Timetable')->getLastOrCreateTable();

        $criteria = [];

        if ($this->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $criteria['manager'] = $this->getUser();
        }

        $timetableRows = $em->getRepository('AppBundle:TimetableRow')->findBy($criteria);

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
                $show = 'general_manager';
                break;
            default:
                throw new NotImplementedException('Not implemented.');
                break;
        }

        /* TODO test test test */
        $show = 'all';

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

        $timetableRowTimesRepository = $em->getRepository('AppBundle:TimetableRowTimes');

        $rows = [];
        foreach ($timetableRows as $timetableRow) {
            $row = [
                'id' => $timetableRow->getId(),
            ];

            $manager = $timetableRow->getManager();
            $customer = $timetableRow->getCustomer();
            $provider = $timetableRow->getProvider();

            $customerPaid = 0;
            /** @var Payment $payment */
            foreach ($customer->getPayments() as $payment) {
                $customerPaid += $payment->getAmount();
            }

            $providerPaid = 0;
            /** @var Payment $payment */
            foreach ($provider->getPayments() as $payment) {
                $providerPaid += $payment->getAmount();
            }

            $times = $timetableRowTimesRepository->getTimesOrCreate($timetable, $timetableRow);
            $sumTimes = $timetableRowTimesRepository->sumTimes($times);
            $customerSalary = $timetableRow->getPriceForCustomer() * $sumTimes;
            $providerSalary = $timetableRow->getPriceForProvider() * $sumTimes;
            $customerBalance = $customerPaid - $timetableRowTimesRepository->calculateContractorBalance($timetable, $customer);
            $providerBalance = $providerPaid - $timetableRowTimesRepository->calculateContractorBalance($timetable, $provider);
            $marginSum = $providerSalary - $customerSalary;

            if ($providerSalary) {
                $marginPercent = 100-($customerSalary/$providerSalary*100);
            } else {
                $marginPercent = 0;
            }

            foreach ($columns as $column) {
                switch ($column) {
                    case 'manager':
                        $value = $manager->getUsername();
                        break;
                    case 'customer':
                        $value = $customer->getName();
                        break;
                    case 'provider':
                        $value = $provider->getName();
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
                        $value = $times;
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
                        break;
                    case 'provider_balance':
                        $value = $providerBalance;
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

        return $this->render(
            '@App/default/index.html.twig',
            [
                'timetable' => $timetable,
                'rows' => $rows,
                'columns' => $columns,
                'num_of_fixed' => $numOfFixed,
                'fixed_columns' => $fixedColumns,
            ]
        );
    }
}
