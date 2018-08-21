<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Contractor
 *
 * @ORM\Table(name="contractor")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContractorRepository")
 * @UniqueEntity(fields={"inn"}, errorPath="inn", message="Контрагент с таким ИНН уже существует")
 */
class Contractor
{
    const CUSTOMER = 'customer';
    const PROVIDER = 'provider';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="inn", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     */
    private $inn;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Choice(choices={"customer", "provider"})
     */
    private $type;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $manager;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Payment", mappedBy="contractor")
     * @ORM\OrderBy(value={"date"="DESC"})
     */
    private $payments;

    /**
     * @var Organisation
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organisation")
     * @ORM\JoinColumn(name="organisation_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $organisation;

    /**
     * @var string
     *
     * @ORM\Column(name="brand", type="text", nullable=true)
     */
    private $brand;

    /**
     * @var string
     *
     * @ORM\Column(name="businessAddress", type="text", nullable=true)
     */
    private $businessAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="physicalAddress", type="text", nullable=true)
     */
    private $physicalAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="site", type="text", nullable=true)
     */
    private $site;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Object", mappedBy="contractor")
     * @ORM\OrderBy(value={"address"="ASC"})
     */
    private $objects;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Contact", mappedBy="contractor")
     * @ORM\OrderBy(value={"fio"="ASC"})
     */
    private $contacts;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->objects = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Contractor
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set inn
     *
     * @param string $inn
     *
     * @return Contractor
     */
    public function setInn($inn)
    {
        $this->inn = $inn;

        return $this;
    }

    /**
     * Get inn
     *
     * @return string
     */
    public function getInn()
    {
        return $this->inn;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Contractor
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
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
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return ArrayCollection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param ArrayCollection $contacts
     * @return Contractor
     */
    public function setContacts($contacts)
    {
        $this->contacts = $contacts;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * @param ArrayCollection $payments
     * @return Contractor
     */
    public function setPayments($payments)
    {
        $this->payments = $payments;

        return $this;
    }

    /**
     * @return Organisation
     */
    public function getOrganisation()
    {
        return $this->organisation;
    }

    /**
     * @param Organisation $organisation
     * @return Contractor
     */
    public function setOrganisation($organisation)
    {
        $this->organisation = $organisation;

        return $this;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     * @return Contractor
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * @return string
     */
    public function getBusinessAddress()
    {
        return $this->businessAddress;
    }

    /**
     * @param string $businessAddress
     * @return Contractor
     */
    public function setBusinessAddress($businessAddress)
    {
        $this->businessAddress = $businessAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhysicalAddress()
    {
        return $this->physicalAddress;
    }

    /**
     * @param string $physicalAddress
     * @return Contractor
     */
    public function setPhysicalAddress($physicalAddress)
    {
        $this->physicalAddress = $physicalAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param string $site
     * @return Contractor
     */
    public function setSite($site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @param ArrayCollection $objects
     * @return Contractor
     */
    public function setObjects($objects)
    {
        $this->objects = $objects;

        return $this;
    }

    /**
     * @param ExecutionContextInterface $context
     * @Assert\Callback()
     */
    public function isManagerValid(ExecutionContextInterface $context)
    {
        if ($this->getType() == self::CUSTOMER) {
            if (!$this->getManager()) {
                $context
                    ->buildViolation('Поле менеджер по продажам не может быть пустым.')
                    ->atPath('manager')
                    ->addViolation()
                ;
            }
        }
    }
}
