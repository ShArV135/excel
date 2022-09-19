<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Timetable;

class ReportConfig
{
    private $timetableFrom;
    private $timetableTo;
    private $contractor;
    private $byOrganisation = false;
    private $organisation;
    private $timetable;

    public static function fromArray(array $data)
    {
        $self = new static();

        if (!empty($data['timetable_from'])) {
            $self->setTimetableFrom($data['timetable_from']);
        }

        if (!empty($data['timetable_to'])) {
            $self->setTimetableTo($data['timetable_to']);
        }

        if (!empty($data['contractor'])) {
            $self->setContractor($data['contractor']);
        }

        if (!empty($data['by_organisations'])) {
            $self->setByOrganisation($data['by_organisations']);
        }

        return $self;
    }

    public function getTimetableFrom(): ?Timetable
    {
        return $this->timetableFrom;
    }

    public function setTimetableFrom(Timetable $timetableFrom): void
    {
        $this->timetableFrom = $timetableFrom;
    }

    public function getTimetableTo(): ?Timetable
    {
        return $this->timetableTo;
    }

    public function setTimetableTo(Timetable $timetableTo): void
    {
        $this->timetableTo = $timetableTo;
    }

    public function getContractor(): ?Contractor
    {
        return $this->contractor;
    }

    public function setContractor(Contractor $contractor): void
    {
        $this->contractor = $contractor;
    }

    public function getByOrganisation(): bool
    {
        return $this->byOrganisation;
    }

    public function setByOrganisation(bool $byOrganisation): void
    {
        $this->byOrganisation = $byOrganisation;
    }

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(Organisation $organisation): void
    {
        $this->organisation = $organisation;
    }

    public function getTimetable(): ?Timetable
    {
        return $this->timetable;
    }

    public function setTimetable(Timetable $timetable): void
    {
        $this->timetable = $timetable;
    }
}
