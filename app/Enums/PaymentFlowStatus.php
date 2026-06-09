<?php
namespace App\Enums;

class PaymentFlowStatus
{
    const PENDING = 'pending';
    const SUCCESS = 'success';
    const FAILED  = 'failed';
    const REFUND  = 'refund';
}
