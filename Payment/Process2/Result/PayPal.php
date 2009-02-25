<?php
require_once 'Payment/Process2/Result.php';
require_once 'Payment/Process2/Result/Driver.php';

class Payment_Process2_Result_PayPal extends Payment_Process2_Result implements Payment_Process2_Result_Driver
{

    var $_statusCodeMap = array('1' => PAYMENT_PROCESS2_RESULT_APPROVED,
                                '2' => PAYMENT_PROCESS2_RESULT_DECLINED,
                                '3' => PAYMENT_PROCESS2_RESULT_OTHER,
                                '4' => PAYMENT_PROCESS2_RESULT_REVIEW,
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
        '0' => PAYMENT_PROCESS2_AVS_MATCH,
        '1' => PAYMENT_PROCESS2_AVS_MISMATCH,
        '2' => PAYMENT_PROCESS2_AVS_MISMATCH,
        '3' => PAYMENT_PROCESS2_AVS_NOAPPLY,
        '4' => PAYMENT_PROCESS2_AVS_ERROR,
        'A' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'B' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'C' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'D' => PAYMENT_PROCESS2_AVS_MATCH,
        'E' => PAYMENT_PROCESS2_AVS_NOAPPLY,
        'F' => PAYMENT_PROCESS2_AVS_MATCH,
        'G' => PAYMENT_PROCESS2_AVS_NOAPPLY,
        'I' => PAYMENT_PROCESS2_AVS_NOAPPLY,
        'N' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'P' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'R' => PAYMENT_PROCESS2_AVS_ERROR,
        'S' => PAYMENT_PROCESS2_AVS_ERROR,
        'U' => PAYMENT_PROCESS2_AVS_ERROR,
        'W' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'X' => PAYMENT_PROCESS2_AVS_MATCH,
        'Y' => PAYMENT_PROCESS2_AVS_MATCH,
        'Z' => PAYMENT_PROCESS2_AVS_MISMATCH,
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
        '0' => PAYMENT_PROCESS2_CVV_MATCH,
        '1' => PAYMENT_PROCESS2_CVV_MISMATCH,
        '2' => PAYMENT_PROCESS2_CVV_NOAPPLY,
        '3' => PAYMENT_PROCESS2_CVV_NOAPPLY,
        '4' => PAYMENT_PROCESS2_CVV_ERROR,
        'M' => PAYMENT_PROCESS2_CVV_MATCH,
        'N' => PAYMENT_PROCESS2_CVV_MISMATCH,
        'P' => PAYMENT_PROCESS2_CVV_ERROR,
        'S' => PAYMENT_PROCESS2_CVV_NOAPPLY,
        'U' => PAYMENT_PROCESS2_CVV_ERROR,
        'X' => PAYMENT_PROCESS2_CVV_ERROR,
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

    var $_fieldMap = array('0'  => 'code',
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

    /**
     * Parses the data received from the payment gateway
     *
     * @access public
     */
    function parse()
    {
        $responseArray = parse_str($this->_rawResponse);

        // Save some fields in private members
        $map = array_flip($this->_fieldMap);
        $this->_amount = $responseArray[$map['amount']];

        $this->_mapFields($responseArray);

        // Adjust result code/message if needed based on raw code
        switch ($this->messageCode) {
            case 33:
                // Something is missing so we send the raw message back
                $this->_statusCodeMessages[33] = $this->message;
                break;
            case 11:
                // Duplicate transactions
                $this->code = PAYMENT_PROCESS2_RESULT_DUPLICATE;
                break;
            case 4:
            case 41:
            case 250:
            case 251:
                // Fraud detected
                $this->code = PAYMENT_PROCESS2_RESULT_FRAUD;
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
