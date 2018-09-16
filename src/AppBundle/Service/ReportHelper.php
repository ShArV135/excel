<?php

namespace AppBundle\Service;

use AppBundle\Entity\Bonus;
use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class ReportHelper
{
    private $entityManager;
    private $timetableHelper;
    private $authorizationChecker;
    private $tokenStorage;
    private $router;

    /**
     * ReportHelper constructor.
     * @param EntityManager        $entityManager
     * @param TimetableHelper      $timetableHelper
     * @param AuthorizationChecker $authorizationChecker
     * @param TokenStorage         $tokenStorage
     * @param Router               $router
     */
    public function __construct(
        EntityManager $entityManager,
        TimetableHelper $timetableHelper,
        AuthorizationChecker $authorizationChecker,
        TokenStorage $tokenStorage,
        Router $router
    )
    {
        $this->entityManager = $entityManager;
        $this->timetableHelper = $timetableHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    /**
     * @param Timetable         $timetable
     * @param Organisation|null $organisation
     * @param User|null         $user
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getManagerData(Timetable $timetable, Organisation $organisation = null, User $user = null)
    {
        $customerManagers = $providerManagers = [];
        if ($user) {
            if (in_array('ROLE_CUSTOMER_MANAGER', $user->getRoles())) {
                $customerManagers = [$user];
            } else {
                $providerManagers = [$user];
            }
        } else {
            $customerManagers = $this->entityManager->getRepository('AppBundle:User')->getManagers();

            $providerManagers = $this->entityManager->getRepository('AppBundle:User')->getManagers('ROLE_PROVIDER_MANAGER');
        }
        $customerManagerData = [];
        /** @var User $customerManager */
        foreach ($customerManagers as $customerManager) {
            $customerManagerData[] = $this->getManagerRowData($timetable, $customerManager, $organisation);
        }
        $providerManagerData = [];
        /** @var User $providerManager */
        foreach ($providerManagers as $providerManager) {
            $providerManagerData[] = $this->getManagerRowData($timetable, $providerManager, $organisation);;
        }

        $planData = $this->timetableHelper->planData($timetable);

        $customerSummaryData = [
            'salary' => array_sum(array_column($customerManagerData, 'salary')),
            'margin_sum' => array_sum(array_column($customerManagerData, 'margin_sum')),
            'bonus' => array_sum(array_column($customerManagerData, 'bonus')),
            'margin_percent' => 0,
            'plan_completed_percent' => $planData['plan_completed_percent'],
        ];
        if ($count = count(array_filter(array_column($customerManagerData, 'margin_percent')))) {
            $customerSummaryData['margin_percent'] = array_sum(array_column($customerManagerData, 'margin_percent')) / $count;
        }

        $providerSummaryData = [
            'salary' => array_sum(array_column($providerManagerData, 'salary')),
            'margin_sum' => array_sum(array_column($providerManagerData, 'margin_sum')),
            'bonus' => array_sum(array_column($customerManagerData, 'bonus')),
            'margin_percent' => 0,
        ];
        if ($count = count(array_filter(array_column($providerManagerData, 'margin_percent')))) {
            $providerSummaryData['margin_percent'] = array_sum(array_column($providerManagerData, 'margin_percent')) / $count;
        }

        return [
            'customer_summary_data' => $customerSummaryData,
            'provider_summary_data' => $providerSummaryData,
            'customer_manager_data' => $customerManagerData,
            'provider_manager_data' => $providerManagerData,
        ];
    }

    /**
     * @param array             $filter
     * @param Organisation|null $organisation
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getSalesData(array $filter, Organisation $organisation = null)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $salesData = [];
        if (!empty($filter['timetable'])) {
            $timetables = $filter['timetable'];
        } else {
            $timetables = $this->entityManager->getRepository('AppBundle:Timetable')->findAll();

            if (empty($filter['customer'])) {
                $criteria = [
                    'type' => Contractor::CUSTOMER,
                ];

                if ($this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER')) {
                    $criteria['manager'] = $user;
                } else {
                    if (!empty($filter['manager'])) {
                        $criteria['manager'] = $filter['manager'];
                    }
                }

                if ($organisation) {
                    $criteria['organisation'] = $organisation;
                }

                $customers = $this->entityManager->getRepository('AppBundle:Contractor')->findBy($criteria, ['name' => 'ASC']);
                foreach ($customers as $customer) {
                    $salesData[$customer->getId()] = [
                        'name' => $customer->getName(),
                        'balance' => $this->timetableHelper->contractorBalance($customer),
                        'manager' => $customer->getManager()->getFullName(),
                        'salary' => 0,
                        'margin_sum' => 0,
                        'margin_percent' => 0,
                        'counter' => 0,
                    ];
                }
            }
        }

        $criteria = [
            'timetable' => $timetables,
        ];
        if ($this->authorizationChecker->isGranted('ROLE_CUSTOMER_MANAGER')) {
            $criteria['manager'] = $user;
        } else {
            if (!empty($filter['manager'])) {
                $criteria['manager'] = $filter['manager'];
            }
        }

        if (!empty($filter['customer'])) {
            $criteria['customer'] = $filter['customer'];
        }
        $timetableRows = $this->entityManager->getRepository('AppBundle:TimetableRow')->findBy($criteria);

        foreach ($timetableRows as $timetableRow) {
            $customer = $timetableRow->getCustomer();

            if ($organisation && $customer->getOrganisation() !== $organisation) {
                continue;
            }

            if (!isset($salesData[$customer->getId()])) {
                $salesData[$customer->getId()] = [
                    'manager' => $timetableRow->getManager()->getFullName(),
                    'name' => $customer->getName(),
                    'salary' => 0,
                    'balance' => $this->timetableHelper->contractorBalance($customer),
                    'margin_sum' => 0,
                    'margin_percent' => 0,
                    'counter' => 0,
                ];
            }

            $rowData = $this->timetableHelper->calculateRowData($timetableRow);

            $salesData[$customer->getId()]['salary'] += $rowData['customer_salary'];
            $salesData[$customer->getId()]['margin_sum'] += $rowData['margin_sum'];
            $salesData[$customer->getId()]['margin_percent'] += $rowData['margin_percent'];
            $salesData[$customer->getId()]['counter']++;
        }

        foreach ($salesData as $i => $data) {
            if ($data['counter'] > 0) {
                $salesData[$i]['margin_percent'] = $data['margin_percent'] / $data['counter'];
            }

            if ($bonus = $this->getBonus(Bonus::MANAGER_TYPE_CUSTOMER)) {
                switch ($bonus->getType()) {
                    case Bonus::TYPE_FROM_SALARY:
                        $salesData[$i]['bonus'] = $data['salary'] * $bonus->getValue() / 100;
                        break;
                    case Bonus::TYPE_FROM_MARGIN:
                        $salesData[$i]['bonus'] = $data['margin_sum'] * $bonus->getValue() / 100;
                        break;
                    default:
                        $salesData[$i]['bonus'] = 0;
                }
            } else {
                $salesData[$i]['bonus'] = 0;
            }
        }

        $summaryData = [
            'salary' => array_sum(array_column($salesData, 'salary')),
            'balance' => array_sum(array_column($salesData, 'balance')),
            'margin_sum' => array_sum(array_column($salesData, 'margin_sum')),
            'bonus' => array_sum(array_column($salesData, 'bonus')),
            'margin_percent' => 0,
        ];
        if ($count = count(array_filter(array_column($salesData, 'margin_percent')))) {
            $summaryData['margin_percent'] = array_sum(array_column($salesData, 'margin_percent')) / $count;
        }

        return [
            'sales_data' => $salesData,
            'summary_data' => $summaryData,
        ];
    }

    /**
     * @param array        $filter
     * @param Organisation $organisation
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getProvideData(array $filter, Organisation $organisation = null)
    {
        $provideData = [];
        if (!empty($filter['timetable'])) {
            $timetables = $filter['timetable'];
        } else {
            $timetables = $this->entityManager->getRepository('AppBundle:Timetable')->findAll();

            if (empty($filter['provider'])) {
                $providers = $this->entityManager->getRepository('AppBundle:Contractor')->findBy(['type' => Contractor::PROVIDER], ['name' => 'ASC']);
                foreach ($providers as $provider) {
                    if ($organisation && $provider->getOrganisation() !== $organisation) {
                        continue;
                    }

                    $provideData[$provider->getId()] = [
                        'name' => $provider->getName(),
                        'balance' => $this->timetableHelper->contractorBalance($provider),
                        'manager' => '',
                        'salary' => 0,
                    ];
                }
            }
        }

        $criteria = [
            'timetable' => $timetables,
        ];
        if (!empty($filter['provider'])) {
            $criteria['provider'] = $filter['provider'];
        }
        $timetableRows = $this->entityManager->getRepository('AppBundle:TimetableRow')->findBy($criteria);

        foreach ($timetableRows as $timetableRow) {
            $provider = $timetableRow->getProvider();

            if (!$provider) {
                continue;
            }

            if ($organisation && $provider->getOrganisation() !== $organisation) {
                continue;
            }

            if (!isset($provideData[$provider->getId()])) {
                $provideData[$provider->getId()] = [
                    'name' => $provider->getName(),
                    'salary' => 0,
                    'balance' => $this->timetableHelper->contractorBalance($provider),
                ];
            }

            $rowData = $this->timetableHelper->calculateRowData($timetableRow);

            $provideData[$provider->getId()]['salary'] += $rowData['provider_salary'];
        }

        $summaryData = [
            'salary' => array_sum(array_column($provideData, 'salary')),
            'balance' => array_sum(array_column($provideData, 'balance')),
        ];

        return [
            'provide_data' => $provideData,
            'summary_data' => $summaryData,
        ];
    }

    /**
     * @param Timetable         $timetable
     * @param User              $user
     * @param Organisation|null $organisation
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function getManagerRowData(Timetable $timetable, User $user, Organisation $organisation = null)
    {
        $isCustomer = in_array('ROLE_CUSTOMER_MANAGER', $user->getRoles());

        if ($isCustomer) {
            $timetableRows = $this->entityManager->getRepository('AppBundle:TimetableRow')->findBy([
                'manager' => $user,
                'timetable' => $timetable,
            ]);
        } else {
            $timetableRows = $this->entityManager->getRepository('AppBundle:TimetableRow')->findBy([
                'providerManager' => $user,
                'timetable' => $timetable,
            ]);
        }

        $row = [
            'fio' => $user->getFullName(),
            'detail_url' => $this->router->generate('report_manager_detail', ['user' => $user->getId(), 'timetable' => $timetable->getId()]),
            'salary' => 0,
            'margin_sum' => 0,
            'margin_percent' => 0,
            'balance_negative' => 0,
            'contractors' => 0
        ];

        foreach ($timetableRows as $timetableRow) {
            if ($organisation) {
                if ($isCustomer) {
                    $contractor = $timetableRow->getCustomer();
                } else {
                    $contractor = $timetableRow->getProvider();
                }
                if (!$contractor || $contractor->getOrganisation() !== $organisation) {
                    continue;
                }
            }

            $rowData = $this->timetableHelper->calculateRowData($timetableRow);

            $row['salary'] += $rowData['customer_salary'];
            $row['margin_sum'] += $rowData['margin_sum'];
            $row['margin_percent'] += $rowData['margin_percent'];
        }

        if ($timetableRows) {
            $row['margin_percent'] = $row['margin_percent'] / count($timetableRows);
        }

        $criteria = [
            'manager' => $user,
        ];

        if ($organisation) {
            $criteria['organisation'] = $organisation;
        }

        $contractors = $this->entityManager->getRepository('AppBundle:Contractor')->findBy($criteria, [
            'name' => 'ASC',
        ]);

        foreach ($contractors as $contractor) {
            $balance = $this->timetableHelper->contractorBalance($contractor);

            if ($balance < 0) {
                $row['balance_negative'] += $balance;
            }
        }
        $row['contractors'] = count($contractors);

        if ($isCustomer) {
            $planData = $this->timetableHelper->planData($timetable, $user);
            $row['plan_completed_percent'] = $planData['plan_completed_percent'];
        }

        if ($bonus = $this->getBonus($isCustomer ? Bonus::MANAGER_TYPE_CUSTOMER : Bonus::MANAGER_TYPE_PROVIDER)) {
            switch ($bonus->getType()) {
                case Bonus::TYPE_FROM_SALARY:
                    $row['bonus'] = $row['salary'] * $bonus->getValue() / 100;
                    break;
                case Bonus::TYPE_FROM_MARGIN:
                    $row['bonus'] = $row['margin_sum'] * $bonus->getValue() / 100;
                    break;
                default:
                    $row['bonus'] = 0;
            }
        } else {
            $row['bonus'] = 0;
        }

        return $row;
    }

    /**
     * @param $type
     * @return Bonus
     */
    private function getBonus($type)
    {
        static $bonuses;

        if (!isset($bonuses[$type])) {
            $bonuses[$type] = $this->entityManager->getRepository('AppBundle:Bonus')->findOneBy(['managerType' => $type]);
        }

        return $bonuses[$type];
    }
}
