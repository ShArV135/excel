<?php

namespace AppBundle\Service;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\TimetableRowTimes;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class TimetableHelper
{
    private $entityManager;
    private $authorizationChecker;
    private $request;
    private $router;

    /**
     * TimetableHelper constructor.
     * @param EntityManager        $entityManager
     * @param AuthorizationChecker $authorizationChecker
     * @param RequestStack         $requestStack
     * @param Router               $router
     */
    public function __construct(EntityManager $entityManager, AuthorizationChecker $authorizationChecker, RequestStack $requestStack, Router $router)
    {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
    }

    /**
     * @param TimetableRow $timetableRow
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function calculateRowData(TimetableRow $timetableRow)
    {
        $timetable = $timetableRow->getTimetable();
        $timetableRowTimesRepository = $this->entityManager->getRepository('AppBundle:TimetableRowTimes');
        $paymentRepository = $this->entityManager->getRepository('AppBundle:Payment');

        $customer = $timetableRow->getCustomer();
        $provider = $timetableRow->getProvider();

        $customerPayments = $paymentRepository->getByContractorAndTimetable($customer, $timetable);
        $customerPaid = 0;
        /** @var Payment $payment */
        foreach ($customerPayments as $payment) {
            $customerPaid += $payment->getAmount();
        }

        $providerPayments = $paymentRepository->getByContractorAndTimetable($customer, $timetable);
        $providerPaid = 0;
        /** @var Payment $payment */
        foreach ($providerPayments as $payment) {
            $providerPaid += $payment->getAmount();
        }

        $timetableRowTimes = $timetableRowTimesRepository->getTimesOrCreate($timetableRow);
        $times = $timetableRowTimes->getTimes();
        $sumTimes = $timetableRowTimesRepository->sumTimes($times);
        $customerSalary = $timetableRow->getPriceForCustomer() * $sumTimes;
        $providerSalary = $timetableRow->getPriceForProvider() * $sumTimes;
        $marginSum = $customerSalary - $providerSalary;
        $customerBalance = $this->contractorBalance($customer, $timetable);

        if ($provider) {
            $providerBalance = $this->contractorBalance($provider, $timetable);
        } else {
            $providerBalance = 0;
        }

        if ($providerSalary) {
            $marginPercent = round(100-($providerSalary/$customerSalary*100), 2);
        } else {
            $marginPercent = 0;
        }

        return [
            'timetable_row_times' => $timetableRowTimes,
            'sum_times' => $sumTimes,
            'customer_salary' => $customerSalary,
            'provider_salary' => $providerSalary,
            'customer_balance' => $customerBalance,
            'provider_balance' => $providerBalance,
            'margin_sum' => $marginSum,
            'margin_percent' => $marginPercent,
            'customer_paid' => $customerPaid,
            'provider_paid' => $providerPaid,
            'customer_id' => $customer->getId(),
            'provider_id' => $provider ? $provider->getId() : null,
        ];
    }

    /**
     * @param Contractor $contractor
     * @param Timetable  $timetable
     * @return float|int
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function contractorBalance(Contractor $contractor, Timetable $timetable = null)
    {
        $timetableRowTimesRepository = $this->entityManager->getRepository('AppBundle:TimetableRowTimes');
        $paymentRepository = $this->entityManager->getRepository('AppBundle:Payment');

        if (!$timetable) {
            $timetable = $this->entityManager->getRepository('AppBundle:Timetable')->getCurrent();
        }

        $payments = $paymentRepository->getByContractorAndDate($contractor, clone $timetable->getCreated()->modify('last day of'));
        $paid = 0;
        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $paid += $payment->getAmount();
        }

        $balance = $paid - $timetableRowTimesRepository->calculateContractorBalance($timetable, $contractor);

        return $balance;
    }

    /**
     * @return mixed|string
     */
    public function getShowMode()
    {
        switch (true) {
            case $this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER'):
                $show = 'customer_manager';
                break;
            case $this->authorizationChecker->isGranted('ROLE_DISPATCHER'):
                $show = 'dispatcher';
                break;
            case $this->authorizationChecker->isGranted('ROLE_PROVIDER_MANAGER'):
                $show = 'provider_manager';
                break;
            case $this->authorizationChecker->isGranted('ROLE_MANAGER'):
                $show = $this->request->get('show', 'general_manager');
                break;
            default:
                throw new NotImplementedException('Not implemented.');
                break;
        }

        return $show;
    }

    /**
     * @param $show
     * @return array
     */
    public function getColumnsByShow($show)
    {
        switch ($show) {
            case 'customer_manager':
                $columns = [
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

        if (!$this->authorizationChecker->isGranted('ROLE_GENERAL_MANAGER')) {
            $index = array_search('margin_sum', $columns);

            if ($index !== false) {
                unset($columns[$index]);
            }
        }

        return $columns;
    }

    /**
     * @param TimetableRow $timetableRow
     * @param array        $columns
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function timetableRowFormat(TimetableRow $timetableRow, array $columns)
    {
        $manager = $timetableRow->getManager();
        $customer = $timetableRow->getCustomer();
        $provider = $timetableRow->getProvider();

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
            ) = array_values($this->calculateRowData($timetableRow));

        if (in_array('manager', $columns)) {
            $managersById = $this->entityManager->getRepository('AppBundle:User')->getManagersById();
        } else {
            $managersById = [];
        }

        $row = [
            'id' => $timetableRow->getId(),
            'customer_id' => $customer->getId(),
        ];

        if ($provider) {
            $row['provider_id'] = $provider->getId();
        }

        $row['controls'] = [
            'update' => $this->router->generate('timetable_row_update', ['timetableRow' => $timetableRow->getId()]),
            'delete' => $this->router->generate('timetable_row_delete', ['timetableRow' => $timetableRow->getId()]),
        ];

        foreach ($columns as $column) {
            switch ($column) {
                case 'manager':
                    $value = $managersById[$manager->getId()];
                    break;
                case 'customer':
                    $value = [
                        'url' => $this->router->generate('contractor_view', ['contractor' => $customer->getId()]),
                        'name' => $customer->getName(),
                    ];
                    break;
                case 'provider':
                    if ($provider) {
                        $value = [
                            'url' => $this->router->generate('contractor_view', ['contractor' => $provider->getId()]),
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
                    $value = number_format($timetableRow->getPriceForCustomer(), 2, '.', ' ');
                    break;
                case 'price_for_provider':
                    $value = number_format($timetableRow->getPriceForProvider(), 2, '.', ' ');
                    break;
                case 'sum_times':
                    $value = number_format($sumTimes, 0, '.', ' ');
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
                            'comment_url' => $this->router->generate(
                                'timetable_row_times_update_comment',
                                [
                                    'timetableRowTimes' => $timetableRowTimes->getId(),
                                ]
                            ),
                            'time' => $time,
                        ];
                        $row['_times_'.$day.'_class'] = 'times '.$colors[$day];

                        if ($day == 16) {
                            $row['_times_'.$day.'_class'] .= ' bold-border';
                        }

                        $row['_times_'.$day.'_data'] = [
                            'id' => $timetableRowTimes->getId(),
                            'day' => $day,
                        ];
                    }
                    $value = $timetableRowTimes;
                    break;
                case 'customer_salary':
                    $value = number_format($customerSalary, 2, '.', ' ');
                    break;
                case 'provider_salary':
                    $value = number_format($providerSalary, 2, '.', ' ');
                    break;
                case 'customer_paid':
                    $value = number_format($customerPaid, 2, '.', ' ');
                    break;
                case 'provider_paid':
                    $value = number_format($providerPaid, 2, '.', ' ');
                    break;
                case 'customer_balance':
                    $value = number_format($customerBalance, 2, '.', ' ');

                    if ($customerBalance < 0) {
                        $row['_customer_balance_class'] = 'customer_balance bg-red text-white';
                    } else {
                        $row['_customer_balance_class'] = 'customer_balance';
                    }
                    break;
                case 'provider_balance':
                    $value = number_format($providerBalance, 2, '.', ' ');

                    if ($providerBalance < 0) {
                        $row['_provider_balance_class'] = 'provider_balance bg-pink';
                    } else {
                        $row['_provider_balance_class'] = 'provider_balance';
                    }
                    break;
                case 'margin_sum':
                    $value = number_format($marginSum, 2, '.', ' ');
                    break;
                case 'margin_percent':
                    $value = number_format($marginPercent, 2, '.', ' ');
                    break;
                default:
                    $value = '';
            }

            $row[$column] = $value;
        }

        return $row;
    }
}
