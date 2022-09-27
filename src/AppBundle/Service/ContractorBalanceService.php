<?php

namespace AppBundle\Service;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\ContractorBalance;
use AppBundle\Entity\Timetable;
use Doctrine\ORM\EntityManagerInterface;

class ContractorBalanceService
{
    private $storage;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->storage = new \SplObjectStorage();
        $this->entityManager = $entityManager;
    }

    public function init(Timetable $timetable): void
    {
        $balances = $this->entityManager->getRepository(ContractorBalance::class)->balancePerContractor($timetable);

        $this->storage->attach($timetable, $balances);
    }

    public function invalidate(): void
    {
        $this->storage = new \SplObjectStorage();
    }

    public function getBalance(Contractor $contractor, Timetable $timetable = null): float
    {
        if (!$timetable) {
            $timetable = $this->entityManager->getRepository(Timetable::class)->getCurrent();
        }

        $value = $this->getOne($contractor, $timetable);

        if (!is_null($value)) {
            return $value;
        }

        $this->init($timetable);
        return $this->getOne($contractor, $timetable) ?? 0.0;
    }

    private function getOne(Contractor $contractor, Timetable $timetable): ?float
    {
        return $this->storage[$timetable][$contractor->getId()] ?? null;
    }
}
