<?php
require_once 'Payment/Process2/Result.php';
require_once 'Payment/Process2/Result/Driver.php';

class Payment_Process2_Result_PayPal extends Payment_Process2_Result implements Payment_Process2_Result_Driver
{

    var $_statusCodeMap = array('1' => Payment_Process2::RESULT_APPROVED,
                                '2' => Payment_Process2::RESULT_DECLINED,
                                '3' => Payment_Process2::RESULT_OTHER,
                                '4' => Payment_Process2::RESULT_REVIEW,
                                );
    /**
     * PayPal error codes
     *
     * This array holds many of the common response codes. There are over 200
     * response codes - so check the PayPal manual if you get a status
     * code that does not match (see "Error Reference Message" in the NVAPI
     * Developer Guide).
     *
     * @see getStatusText()
     * @access private
     */
    var $_statusCodeMessages = array();

    var $_avsCodeMap = array(
        '0' => Payment_Process2::AVS_MATCH,
        '1' => Payment_Process2::AVS_MISMATCH,
        '2' => Payment_Process2::AVS_MISMATCH,
        '3' => Payment_Process2::AVS_NOAPPLY,
        '4' => Payment_Process2::AVS_ERROR,
        'A' => Payment_Process2::AVS_MISMATCH,
        'B' => Payment_Process2::AVS_MISMATCH,
        'C' => Payment_Process2::AVS_MISMATCH,
        'D' => Payment_Process2::AVS_MATCH,
        'E' => Payment_Process2::AVS_NOAPPLY,
        'F' => Payment_Process2::AVS_MATCH,
        'G' => Payment_Process2::AVS_NOAPPLY,
        'I' => Payment_Process2::AVS_NOAPPLY,
        'N' => Payment_Process2::AVS_MISMATCH,
        'P' => Payment_Process2::AVS_MISMATCH,
        'R' => Payment_Process2::AVS_ERROR,
        'S' => Payment_Process2::AVS_ERROR,
        'U' => Payment_Process2::AVS_ERROR,
        'W' => Payment_Process2::AVS_MISMATCH,
        'X' => Payment_Process2::AVS_MATCH,
        'Y' => Payment_Process2::AVS_MATCH,
        'Z' => Payment_Process2::AVS_MISMATCH,
    );

    var $_avsCodeMessages = array(
        '0' => 'Address and postal code match',
        '1' => 'No match on street address nor postal code',
        '2' => 'Only part of your address information matches',
        '3' => 'Address information unavailable',
        '4' => 'System unavailable or timeout',
        'A' => 'Address matches, postal code does not',
        'B' => 'Address matches, postal code does not',
        'C' => 'No match on street address nor postal code',
        'D' => 'Address and full postal code match',
        'E' => 'Address verification not allowed from Internet/phone',
        'F' => 'Address and full postal code match',
        'G' => 'International Card Issuing Bank',
        'I' => 'International Card Issuing Bank',
        'N' => 'No match on street address nor postal code',
        'P' => 'Postal code matches, street address does not',
        'R' => 'Retry - System unavailable or timeout',
        'S' => 'Service not supported by issuer',
        'U' => 'Address information unavailable',
        'W' => 'Full postal code matches, street address does not',
        'X' => 'Address and full postal code match',
        'Y' => 'Address and postal code match',
        'Z' => 'Postal code matches, street address does not',
    );

    var $_cvvCodeMap = array(
        '0' => Payment_Process2::CVV_MATCH,
        '1' => Payment_Process2::CVV_MISMATCH,
        '2' => Payment_Process2::CVV_NOAPPLY,
        '3' => Payment_Process2::CVV_NOAPPLY,
        '4' => Payment_Process2::CVV_ERROR,
        'M' => Payment_Process2::CVV_MATCH,
        'N' => Payment_Process2::CVV_MISMATCH,
        'P' => Payment_Process2::CVV_ERROR,
        'S' => Payment_Process2::CVV_NOAPPLY,
        'U' => Payment_Process2::CVV_ERROR,
        'X' => Payment_Process2::CVV_ERROR,
    );

    var $_cvvCodeMessages = array(
        '0' => 'Security code matches',
        '1' => 'Security code does not match',
        '2' => 'Security code verification not supported',
        '3' => 'Card does not have security code',
        '4' => 'Issuer unable to process request',
        'M' => 'Security code matches',
        'N' => 'Security code does not match',
        'P' => 'Security code was not processed',
        'S' => 'Security code verification not supported',
        'U' => 'Issuer unable to process request',
        'X' => 'Security could not be verified',
    );

    /**
     * Parses the data received from the payment gateway
     *
     * @access public
     */
    function parse()
    {
        $responseArray = array();

        parse_str($this->_rawResponse, $responseArray);

        $fieldMap = array('0'  => 'code',
                           '2'  => 'messageCode',
                           '3'  => 'message',
                           '4'  => 'approvalCode',
                           '5'  => 'avsCode',
                           '6'  => 'transactionId',
                           '7'  => 'invoiceNumber',
                           '8'  => 'description',
                           '9'  => 'amount',
                           '12' => 'customerId',
                           '38' => 'cvvCode',
        );

        foreach ($fieldMap as $key => $val) {
            $this->$val = (array_key_exists($key, $responseArray))
                          ? $responseArray[$key]
                          : null;
        }
        // Adjust result code/message if needed based on raw code
        switch ($this->messageCode) {
            case 33:
                // Something is missing so we send the raw message back
                $this->_statusCodeMessages[33] = $this->message;
                break;
            case 11:
                // Duplicate transactions
                $this->code = Payment_Process2::RESULT_DUPLICATE;
                break;
            case 4:
            case 41:
            case 250:
            case 251:
                // Fraud detected
                $this->code = Payment_Process2::RESULT_FRAUD;
                break;
        }
    }

    /**
     * Parses the data received from the payment gateway callback
     *
     * @access public
     * @todo Implement support for PayPal's IPN?
     */
    function parseCallback()
    {
        return parent::parseCallback();
    }

    /**
     * Validates the legitimacy of the response
     *
     * @return mixed TRUE if response is legitimate, FALSE if not, PEAR_Error on error
     * @access public
     * @todo Implement support for PayPal's IPN?
     */
    function isLegitimate()
    {
        return true;
    }
}
