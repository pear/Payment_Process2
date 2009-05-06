<?php
require_once 'Payment/Process2/Type/CreditCard.php';

class Payment_Process2_Type_MockCreditCard extends Payment_Process2_Type_CreditCard
{

    public function validate()
    {
        return true;
    }

    /**
     * @todo When we swap to 0.2.0, remove this method in favor of validate()
     */
    function _validateCardNumber()
    {
        return true;
    }

    /**
     * @todo When we swap to 0.2.0, remove this method in favor of validate()
     */
    function _validateType()
    {
        return true;
    }

    /**
     * @todo When we swap to 0.2.0, remove this method in favor of validate()
     */
    function _validateCvv()
    {
        return true;
    }

    /**
     * @todo When we swap to 0.2.0, remove this method in favor of validate()
     */
    function _validateExpDate()
    {
        return true;
    }

    /**
     * @todo When we swap to 0.2.0, remove this method in favor of validate()
     */
    function _validateEmail()
    {
        return true;
    }

    /**
     * @todo When we swap to 0.2.0, remove this method in favor of validate()
     */
    function _validateZip()
    {
        return true;
    }
}