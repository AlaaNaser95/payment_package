<?php


namespace beinmedia\payment\Services;

use beinmedia\payment\Parameters\PaymentParameters;

interface PaymentInterface{
    /**
     * @param PaymentParameters $data
     * @return mixed
     */
    public function generatePaymentURL($data);
    public function isPaymentExecuted();
    public function getPayment($id);
}
