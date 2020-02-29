<?php


namespace beinmedia\payment\Services;
interface PaymentInterface{
    public function generatePaymentURL($data);
    public function isPaymentExecuted();
}
