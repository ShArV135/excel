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
     * @var string
     *
     * @ORM\Column(name="contacts", type="text", nullable=true)
     */
    private $contacts;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     */
    private $manager;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Payment", mappedBy="contractor")
     * @ORM\OrderBy(value={"date"="DESC"})
     */
    private $payments;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
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
     * @return string
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param string $contacts
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
