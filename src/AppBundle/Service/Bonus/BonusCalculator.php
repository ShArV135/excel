<?php

namespace AppBundle\Service\Bonus;

use AppBundle\Entity\Bonus;

class BonusCalculator
{
    private $bonus;
    private $salary;
    private $marginSum;

    public function __construct(Bonus $bonus, float $salary, float $marginSum)
    {
        $this->bonus = $bonus;
        $this->salary = $salary;
        $this->marginSum = $marginSum;
    }

    public function calculate(): float
    {
        switch ($this->bonus->getType()) {
            case Bonus::TYPE_FROM_SALARY:
                return $this->salary * $this->bonus->getValue() / 100;
            case Bonus::TYPE_FROM_MARGIN:
                return $this->marginSum * $this->bonus->getValue() / 100;
            default:
                return 0;
        }
    }
}
