<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Organisation;

interface SummaryInterface
{
    public function getReports(): array;
    public function getOrganisation(): ?Organisation;
}
