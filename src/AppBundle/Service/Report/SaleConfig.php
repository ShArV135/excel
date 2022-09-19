<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Contractor;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Timetable;
use AppBundle\Entity\User;

class SaleConfig extends ReportConfig
{
    private $timetableFrom;
    private $timetableTo;
    private $customer;
    private $byOrganisation = false;
    private $manager;
    private $organisation;
    private $timetable;

    public static function fromArray(array $data): self
    {
        $self = parent::fromArray($data);

        if (!empty($data['manager'])) {
            $self->setManager($data['manager']);
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

    public function getCustomer(): ?Contractor
    {
        return $this->customer;
    }

    public function setCustomer(Contractor $customer): void
    {
        $this->customer = $customer;
    }

    public function getByOrganisation(): bool
    {
        return $this->byOrganisation;
    }

    public function setByOrganisation(bool $byOrganisation): void
    {
        $this->byOrganisation = $byOrganisation;
    }

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(User $manager): void
    {
        $this->manager = $manager;
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
