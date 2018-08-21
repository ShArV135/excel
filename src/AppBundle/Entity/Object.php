<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contractor
 *
 * @ORM\Table(name="object")
 * @ORM\Entity
 */
class Object
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
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="work_description", type="text")
     */
    private $workDescription;

    /**
     * @var Contractor
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Contractor", inversedBy="objects")
     * @ORM\JoinColumn(name="contractor_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * @Assert\NotBlank()
     */
    private $contractor;

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
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return Object
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Object
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getWorkDescription()
    {
        return $this->workDescription;
    }

    /**
     * @param string $workDescription
     * @return Object
     */
    public function setWorkDescription($workDescription)
    {
        $this->workDescription = $workDescription;

        return $this;
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
     * @return Object
     */
    public function setContractor($contractor)
    {
        $this->contractor = $contractor;

        return $this;
    }
}
