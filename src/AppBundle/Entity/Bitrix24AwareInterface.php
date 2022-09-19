<?php

namespace AppBundle\Entity;

interface Bitrix24AwareInterface
{
    public function getBitrix24Id();
    public function setBitrix24Id($bitrix24Id);
}
