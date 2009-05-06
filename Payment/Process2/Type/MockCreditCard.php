<?php
require_once 'Payment/Process2/Type/CreditCard.php';

class Payment_Process2_Type_MockCreditCard extends Payment_Process2_Type_CreditCard
{

    /**
     * Validate details of credit card.
     *
     * A mock card is always valid
     *
     * @return bool
     */
    public function validate()
    {
        return true;
    }
}