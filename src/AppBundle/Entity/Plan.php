<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Plan
 *
 * @ORM\Table(name="plan")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PlanRepository")
 */
class Plan
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @Assert\NotBlank()
     */
    private $user;

    /**
     * @var Timetable
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Timetable")
     * @ORM\JoinColumn(name="timetable_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank()
     */
    private $timetable;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     * @Assert\NotBlank()
     * @Assert\Type(type="float", message="Цена должна быть вещественным числом.")
     */
    private $amount;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return Plan
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Timetable
     */
    public function getTimetable()
    {
        return $this->timetable;
    }

    /**
     * @param Timetable $timetable
     * @return Plan
     */
    public function setTimetable($timetable)
    {
        $this->timetable = $timetable;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return Plan
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }
}
