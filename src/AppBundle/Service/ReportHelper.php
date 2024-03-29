<?php

namespace AppBundle\Service;

use AppBundle\Entity\Bonus;
use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class ReportHelper
{
    private $entityManager;
    private $timetableHelper;
    private $planDataService;
    private $router;

    /**
     * ReportHelper constructor.
     * @param EntityManager $entityManager
     * @param TimetableHelper $timetableHelper
     * @param PlanDataService $planDataService
     * @param Router $router
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TimetableHelper $timetableHelper,
        PlanDataService $planDataService,
        RouterInterface $router
    ) {
        $this->entityManager = $entityManager;
        $this->timetableHelper = $timetableHelper;
        $this->planDataService = $planDataService;
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

        $planData = $this->planDataService->planData($timetable);

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
            'bonus' => array_sum(array_column($providerManagerData, 'bonus')),
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
        $isCustomer = $this->isCustomer($user);

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

            if ($isCustomer) {
                $row['salary'] += $rowData['customer_salary'];
            } else {
                $row['salary'] += $rowData['provider_salary'];
            }

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
            $planData = $this->planDataService->planData($timetable, $user);
            $row['plan_completed_percent'] = $planData['plan_completed_percent'];
        }

        $isTop = $this->isTop($user);
        if ($isCustomer) {
            if ($isTop) {
                $bonusType = Bonus::MANAGER_TYPE_TOP_CUSTOMER;
            } else {
                $bonusType = Bonus::MANAGER_TYPE_CUSTOMER;
            }
        } elseif ($isTop) {
            $bonusType = Bonus::MANAGER_TYPE_TOP_PROVIDER;
        } else {
            $bonusType = Bonus::MANAGER_TYPE_PROVIDER;
        }

        if ($bonus = $this->getBonus($bonusType)) {
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

    private function isCustomer(User $user)
    {
        return  (bool) array_intersect(['ROLE_CUSTOMER_MANAGER', 'ROLE_TOP_CUSTOMER_MANAGER'], $user->getRoles());
    }

    private function isTop(User $user)
    {
        return  (bool) array_intersect(['ROLE_TOP_PROVIDER_MANAGER', 'ROLE_TOP_CUSTOMER_MANAGER'], $user->getRoles());
    }
}
