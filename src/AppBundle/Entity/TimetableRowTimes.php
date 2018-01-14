<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Timetable
 *
 * @ORM\Table(name="timetable_row_times")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TimetableRowTimesRepository")
 */
class TimetableRowTimes
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
     * @ORM\JoinColumn(name="timetable_id", referencedColumnName="id")
     */
    private $timetable;

    /**
     * @var TimetableRow
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\TimetableRow")
     * @ORM\JoinColumn(name="timetable_row_id", referencedColumnName="id")
     */
    private $timetableRow;

    /**
     * @var array
     *
     * @ORM\Column(name="times", type="array")
     */
    private $times;

    public function __construct()
    {
        $this->times = array_fill(1, 31, 0);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return TimetableRowTimes
     */
    public function setTimetable($timetable)
    {
        $this->timetable = $timetable;

        return $this;
    }

    /**
     * @return TimetableRow
     */
    public function getTimetableRow()
    {
        return $this->timetableRow;
    }

    /**
     * @param TimetableRow $timetableRow
     * @return TimetableRowTimes
     */
    public function setTimetableRow($timetableRow)
    {
        $this->timetableRow = $timetableRow;

        return $this;
    }

    /**
     * @return array
     */
    public function getTimes()
    {
        return $this->times;
    }

    /**
     * @param array $times
     * @return TimetableRowTimes
     */
    public function setTimes($times)
    {
        $this->times = $times;

        return $this;
    }
}
