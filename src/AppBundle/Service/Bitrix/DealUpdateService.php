<?php

namespace AppBundle\Service\Bitrix;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\TimetableRow;
use AppBundle\Entity\TimetableRowTimes;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DealUpdateService implements EventServiceInterface
{
    private $provider;
    private $entityManager;

    public function __construct(CRestProvider $provider, EntityManagerInterface $entityManager)
    {
        $this->provider = $provider;
        $this->entityManager = $entityManager;
    }

    public function execute(array $data): void
    {
        $id = $this->extractId($data);

        if (!$id) {
            throw new \RuntimeException('ID is not found');
        }

        if ($this->exists($id)) {
            return;
        }

        $dealData = $this->provider->getDeal($id);

        if ($dealData['STAGE_ID'] !== 'EXECUTING') {
            return;
        }

        $this->createEntity($dealData);
    }

    private function extractId(array $data): ?string
    {
        return $data['FIELDS']['ID'] ?? null;
    }

    private function exists(string $id): bool
    {
        return (bool)$this->entityManager->getRepository(TimetableRow::class)->findOneBy([
            'bitrix24Id' => $id,
        ]);
    }

    private function createEntity(array $dealData): void
    {
        $timetableRow = new TimetableRow();
        $timetableRow->setTimetable($this->getCurrentTimetable());
        $timetableRow->setObject($dealData['TITLE']);
        $timetableRow->setBitrix24Id($dealData['ID']);

        if (!empty($dealData['ASSIGNED_BY_ID'])) {
            $this->setManager($timetableRow, $dealData['ASSIGNED_BY_ID']);
        }

        if (!empty($dealData['COMPANY_ID'])) {
            $this->setCustomer($timetableRow, $dealData['COMPANY_ID']);
        }

        $this->entityManager->persist($timetableRow);

        $this->setProductData($timetableRow, $dealData['ID']);

        $this->entityManager->flush();
    }

    private function getCurrentTimetable(): Timetable
    {
        return $this->entityManager->getRepository(Timetable::class)->getCurrent();
    }

    private function setManager(TimetableRow $timetableRow, $bitrix24Id): void
    {
        $manager = $this->entityManager->getRepository(User::class)->findOneBy([
            'bitrix24Id' => $bitrix24Id
        ]);

        $timetableRow->setManager($manager);
    }

    private function setCustomer(TimetableRow $timetableRow, $bitrix24Id): void
    {
        $contractor = $this->entityManager->getRepository(Contractor::class)->findOneBy([
            'bitrix24Id' => $bitrix24Id
        ]);

        $timetableRow->setManager($contractor);
    }

    private function setProductData(TimetableRow $timetableRow, $bitrix24Id): void
    {
        $products = $this->provider->getDealProducts($bitrix24Id);

        foreach ($products as $product) {
            $timetableRow->setMechanism($product['PRODUCT_NAME']);
            $timetableRow->setPriceForCustomer($product['PRICE']);

            $this->setTimes($timetableRow, $product['QUANTITY']);
        }
    }

    private function setTimes(TimetableRow $timetableRow, int $quantity): void
    {
        /** @var TimetableRowTimes $times */
        $times = $this->entityManager->getRepository(TimetableRowTimes::class)->getTimesOrCreate($timetableRow);

        $timesArray = $times->getTimes();
        $today = date('j');
        $timesArray[$today] = $quantity;

        $times->setTimes($timesArray);
    }

}
