<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContractorBalanceRepository")
 */
class ContractorBalance
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Timetable
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Timetable")
     * @ORM\JoinColumn(name="timetable_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $timetable;

    /**
     * @var Contractor
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Contractor")
     * @ORM\JoinColumn(name="contractor_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $contractor;

    /**
     * @var float
     *
     * @ORM\Column(name="balance", type="float")
     */
    private $balance;

    public function __construct(Contractor $contractor, Timetable $timetable, float $balance)
    {
        $this->contractor = $contractor;
        $this->timetable = $timetable;
        $this->balance = $balance;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
    }
}
