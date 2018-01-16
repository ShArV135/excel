<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * TimetableRow
 *
 * @ORM\Table(name="timetable_row")
 * @ORM\Entity
 */
class TimetableRow
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
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     * @Assert\NotBlank()
     */
    private $manager;

    /**
     * @var Contractor
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Contractor")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * @Assert\NotBlank()
     */
    private $customer;

    /**
     * @var Contractor
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Contractor")
     * @ORM\JoinColumn(name="provider_id", referencedColumnName="id")
     */
    private $provider;

    /**
     * @var string
     *
     * @ORM\Column(name="object", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $object;

    /**
     * @var string
     *
     * @ORM\Column(name="mechanism", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $mechanism;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="price_for_customer", type="float")
     * @Assert\NotBlank()
     * @Assert\Type(type="float", message="Цена должна быть вещественным числом.")
     */
    private $price_for_customer;

    /**
     * @var string
     *
     * @ORM\Column(name="price_for_provider", type="string", length=255, nullable=true)
     */
    private $price_for_provider;

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
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param User $manager
     * @return TimetableRow
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @return Contractor
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Contractor $customer
     * @return TimetableRow
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Contractor
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param Contractor $provider
     * @return TimetableRow
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $object
     * @return TimetableRow
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return string
     */
    public function getMechanism()
    {
        return $this->mechanism;
    }

    /**
     * @param string $mechanism
     * @return TimetableRow
     */
    public function setMechanism($mechanism)
    {
        $this->mechanism = $mechanism;

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
     * @return TimetableRow
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string
     */
    public function getPriceForCustomer()
    {
        return $this->price_for_customer;
    }

    /**
     * @param string $price_for_customer
     * @return TimetableRow
     */
    public function setPriceForCustomer($price_for_customer)
    {
        $this->price_for_customer = $price_for_customer;

        return $this;
    }

    /**
     * @return string
     */
    public function getPriceForProvider()
    {
        return $this->price_for_provider;
    }

    /**
     * @param string $price_for_provider
     * @return TimetableRow
     */
    public function setPriceForProvider($price_for_provider)
    {
        $this->price_for_provider = $price_for_provider;

        return $this;
    }
}