<?php

namespace AppBundle\Service;

use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\TimetableRowTimes;
use AppBundle\Service\Timetable\ManagerSalaryService;
use AppBundle\Service\Timetable\MarginSumService;
use AppBundle\Service\Timetable\RowTimeStorage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TimetableHelper
{
    private $authorizationChecker;
    private $request;
    private $router;
    private $balanceService;
    private $salaryService;
    private $timeStorage;
    private $managerNameStorage;

    /**
     * TimetableHelper constructor.
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param RequestStack                  $requestStack
     * @param RouterInterface               $router
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack $requestStack,
        RouterInterface $router,
        ContractorBalanceService $balanceService,
        ManagerSalaryService $salaryService,
        RowTimeStorage $timeStorage,
        ManagerNameStorage $managerNameStorage
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->balanceService = $balanceService;
        $this->salaryService = $salaryService;
        $this->timeStorage = $timeStorage;
        $this->managerNameStorage = $managerNameStorage;
    }

    /**
     * @param TimetableRow $timetableRow
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function calculateRowData(TimetableRow $timetableRow)
    {
        $timetable = $timetableRow->getTimetable();

        $customer = $timetableRow->getCustomer();
        $provider = $timetableRow->getProvider();

        $timetableRowTimes = $this->timeStorage->get($timetableRow);
        $sumTimes = $timetableRowTimes->sumTimes();
        $customerSalary = $this->salaryService->getSalary($timetableRow->getPriceForCustomer(), $sumTimes);
        $providerSalary = $this->salaryService->getSalary($timetableRow->getPriceForProvider(), $sumTimes);
        $marginSum = MarginSumService::getMarginSum($customerSalary, $providerSalary);

        if ($customer) {
            $customerBalance = $this->balanceService->getBalance($customer, $timetable);
        } else {
            $customerBalance = 0;
        }

        if ($provider) {
            $providerBalance = $this->balanceService->getBalance($provider, $timetable);
        } else {
            $providerBalance = 0;
        }

        if ($customerSalary > 0) {
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
            'customer_id' => $customer ? $customer->getId() : null,
            'provider_id' => $provider ? $provider->getId() : null,
        ];
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
                    'year',
                    'month',
                    'customer',
                    'object',
                    'mechanism',
                    'comment',
                    'price_for_customer',
                    'sum_times',
                    'times',
                    'customer_salary',
                    'customer_balance',
                    'customer_organisation',
                ];
                break;
            case 'dispatcher':
                $columns = [
                    'year',
                    'month',
                    'manager',
                    'provider_manager',
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
                    'customer_balance',
                    'provider_balance',
                    'customer_organisation',
                    'provider_organisation',
                ];
                break;
            case 'provider_manager':
                $columns = [
                    'year',
                    'month',
                    'manager',
                    'provider_manager',
                    'object',
                    'provider',
                    'mechanism',
                    'customer',
                    'comment',
                    'price_for_provider',
                    'sum_times',
                    'times',
                    'provider_salary',
                    'provider_balance',
                    'provider_organisation',
                    'customer_balance',
                    'customer_organisation',
                ];
                break;
            case 'general_manager':
                $columns = [
                    'year',
                    'month',
                    'manager',
                    'provider_manager',
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
                    'customer_balance',
                    'provider_balance',
                    'margin_sum',
                    'margin_percent',
                    'customer_organisation',
                    'provider_organisation',
                ];
                break;
            default:
                $columns = [
                    'year',
                    'month',
                    'manager',
                    'provider_manager',
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
                    'customer_balance',
                    'provider_balance',
                    'margin_sum',
                    'margin_percent',
                    'customer_organisation',
                    'provider_organisation',
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function timetableRowFormat(TimetableRow $timetableRow, array $columns)
    {
        $manager = $timetableRow->getManager();
        $providerManager = $timetableRow->getProviderManager();
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
            ) = array_values($this->calculateRowData($timetableRow));

        $row = [
            'id' => $timetableRow->getId(),
        ];

        if ($customer) {
            $row['customer_id'] = $customer->getId();
        }

        if ($provider) {
            $row['provider_id'] = $provider->getId();
        }

        $row['controls'] = [
            'update' => $this->router->generate('timetable_row_update', ['timetableRow' => $timetableRow->getId()]),
            'delete' => $this->router->generate('timetable_row_delete', ['timetableRow' => $timetableRow->getId()]),
        ];

        foreach ($columns as $column) {
            switch ($column) {
                case 'year':
                    $value = $timetableRow->getTimetable()->getCreated()->format('Y');
                    break;
                case 'month':
                    $value = Utils::getMonth($timetableRow->getTimetable()->getCreated());
                    break;
                case 'manager':
                    if ($manager) {
                        $value = $this->managerNameStorage->getCustomer($manager->getId());
                    } else {
                        $value = '';
                    }

                    break;
                case 'provider_manager':
                    if ($providerManager) {
                        $value = $this->managerNameStorage->getProvider($providerManager->getId());
                    } else {
                        $value = '';
                    }
                    break;
                case 'customer':
                    if ($customer) {
                        $value = [
                            'url' => $this->router->generate('contractor_view', ['contractor' => $customer->getId()]),
                            'name' => $customer->getName(),
                        ];
                    } else {
                        $customer = null;
                    }
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
                    $value = [
                        'value' => number_format($sumTimes, 1, '.', ' '),
                        'set_act' => $this->authorizationChecker->isGranted('ROLE_MANAGER')
                            || $this->authorizationChecker->isGranted('ROLE_DISPATCHER'),
                    ];
                    $row['_sum_times_class'] = $timetableRow->isHasAct() ? 'sum_times has-act' : '';
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
                            'disabled' => $timetableRow->isHasAct() && (
                                $this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER')
                                || $this->authorizationChecker->isGranted('ROLE_PROVIDER_MANAGER')
                            ),
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
                case 'customer_organisation':
                    $value = $customer && $customer->getOrganisation() ? $customer->getOrganisation()->getName() : '';
                    break;
                case 'provider_organisation':
                    $value = ($provider && $provider->getOrganisation()) ? $provider->getOrganisation()->getName() : '';
                    break;
                default:
                    $value = '';
            }

            $row[$column] = $value;
        }

        return $row;
    }
}
