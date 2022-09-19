<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait Bitrix24AwareTrait
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bitrix24Id;

    public function getBitrix24Id(): ?int
    {
        return $this->bitrix24Id;
    }

    public function setBitrix24Id($bitrix24Id): void
    {
        $this->bitrix24Id = $bitrix24Id;
    }
}