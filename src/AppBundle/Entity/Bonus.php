<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Plan
 *
 * @ORM\Table(name="bonus")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BonusRepository")
 */
class Bonus
{
    const MANAGER_TYPE_CUSTOMER = 'customer';
    const MANAGER_TYPE_TOP_CUSTOMER = 'top_customer';
    const MANAGER_TYPE_PROVIDER = 'provider';
    const MANAGER_TYPE_TOP_PROVIDER = 'top_provider';

    const TYPE_FROM_SALARY = 'FROM_SALARY';
    const TYPE_FROM_MARGIN = 'FROM_MARGIN';

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
     * @ORM\Column(name="manager_type", type="string")
     * @Assert\NotBlank()
     */
    private $managerType;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string")
     * @Assert\NotBlank()
     */
    private $type;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float")
     * @Assert\NotBlank()
     * @Assert\Type(type="float", message="Значение должно быть вещественным числом.")
     */
    private $value;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getManagerType()
    {
        return $this->managerType;
    }

    /**
     * @param string $managerType
     * @return Bonus
     */
    public function setManagerType($managerType)
    {
        $this->managerType = $managerType;

        return $this;
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
     * @return Bonus
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return Bonus
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function validateType($type)
    {
        return in_array($type, [Bonus::TYPE_FROM_MARGIN, Bonus::TYPE_FROM_SALARY]);
    }
}