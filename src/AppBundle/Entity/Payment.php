<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contractor
 *
 * @ORM\Table(name="payment")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PaymentRepository")
 */
class Payment
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
     * @var Contractor
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Contractor", inversedBy="payments")
     * @ORM\JoinColumn(name="contractor_id", referencedColumnName="id")
     *
     * @Assert\NotBlank()
     */
    private $contractor;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     * @Assert\NotBlank()
     * @Assert\Type(type="float", message="Цена должна быть вещественным числом.")
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Contractor
     */
    public function getContractor()
    {
        return $this->contractor;
    }

    /**
     * @param Contractor $contractor
     *
     *
     * @return Payment
     */
    public function setContractor($contractor)
    {
        $this->contractor = $contractor;

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
     * @return Payment
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return Payment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return Payment
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }
}
