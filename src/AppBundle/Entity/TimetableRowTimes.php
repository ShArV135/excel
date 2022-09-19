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
     * @var TimetableRow
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\TimetableRow")
     * @ORM\JoinColumn(name="timetable_row_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $timetableRow;

    /**
     * @var array
     *
     * @ORM\Column(name="times", type="array")
     */
    private $times;

    /**
     * @var array
     *
     * @ORM\Column(name="comments", type="array")
     */
    private $comments;

    /**
     * @var array
     *
     * @ORM\Column(name="colors", type="array")
     */
    private $colors;

    public function __construct()
    {
        $this->times = array_fill(1, 31, '');
        $this->comments = array_fill(1, 31, '');
        $this->colors = array_fill(1, 31, '');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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

    /**
     * @return array
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param array $comments
     * @return TimetableRowTimes
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * @return array
     */
    public function getColors()
    {
        return $this->colors;
    }

    /**
     * @param array $colors
     * @return TimetableRowTimes
     */
    public function setColors($colors)
    {
        $this->colors = $colors;

        return $this;
    }

    public function sumTimes(): float
    {
        $sumTimes = 0;
        foreach ($this->times as $time) {
            $sumTimes += (float) $time;
        }

        return $sumTimes;
    }
}
