<?php

namespace AppBundle\Event;

use AppBundle\Entity\Payment;
use Symfony\Component\EventDispatcher\Event;

class PaymentEvent extends Event
{
    public const UPDATE = 'payment.update';

    private $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }
}
