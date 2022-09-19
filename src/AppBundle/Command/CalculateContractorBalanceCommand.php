<?php

namespace AppBundle\Command;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Timetable;
use AppBundle\Service\ContractorBalanceCalculateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateUserCommand
 */
class CalculateContractorBalanceCommand extends Command
{
    private $entityManager;
    private $calculateService;

    public function __construct(EntityManagerInterface $entityManager, ContractorBalanceCalculateService $calculateService)
    {
        $this->entityManager = $entityManager;
        $this->calculateService = $calculateService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('custom:calculate-balance')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $contractors = $this->entityManager->getRepository(Contractor::class)->findAll();
        $timetables = $this->entityManager->getRepository(Timetable::class)->findAll();

        foreach ($contractors as $contractor) {
            foreach ($timetables as $timetable) {
                $this->calculateService->update($contractor, $timetable);
            }
        }
        $this->entityManager->flush();
    }
}
