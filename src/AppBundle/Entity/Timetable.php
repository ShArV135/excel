<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Timetable
 *
 * @ORM\Table(name="timetable")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TimetableRepository")
 */
class Timetable
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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\TimetableRow", mappedBy="timetable")
     */
    private $rows;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="date", length=255, nullable=true)
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var boolean
     *
     * @ORM\Column(name="margin_plan", type="boolean")
     */
    private $marginPlan = false;

    public function __construct()
    {
        $this->rows = new ArrayCollection();
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
     * @return Timetable
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
     * @return ArrayCollection
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param ArrayCollection $rows
     * @return Timetable
     */
    public function setRows($rows)
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     * @return Timetable
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     * @return Timetable
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    public function isMarginPlan(): bool
    {
        return $this->marginPlan;
    }

    public function setMarginPlan(bool $marginPlan): void
    {
        $this->marginPlan = $marginPlan;
    }
}
