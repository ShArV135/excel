<?php

namespace AppBundle\Event;

use AppBundle\Entity\TimetableRowTimes;
use Symfony\Component\EventDispatcher\Event;

class TimeEvent extends Event
{
    public const UPDATE = 'time.update';

    private $timetableRowTimes;

    public function __construct(TimetableRowTimes $timetableRowTimes)
    {
        $this->timetableRowTimes = $timetableRowTimes;
    }

    public function getTimetableRowTimes(): TimetableRowTimes
    {
        return $this->timetableRowTimes;
    }
}
