<?php
require_once 'Payment/Process2/Result.php';
require_once 'Payment/Process2/Result/Driver.php';

class Payment_Process2_Result_TrustCommerce extends Payment_Process2_Result implements Payment_Process2_Result_Driver
{

    var $_statusCodeMap = array('approved' => Payment_Process2::RESULT_APPROVED,
                                'accepted' => Payment_Process2::RESULT_APPROVED,
                                'declined' => Payment_Process2::RESULT_DECLINED,
                                'baddata' => Payment_Process2::RESULT_OTHER,
                                'error' => Payment_Process2::RESULT_OTHER);

    /**
     * TrustCommerce status codes
     *
     * This array holds response codes.
     *
     * @see getStatusText()
     * @access private
     */
    var $_statusCodeMessages = array(
        'approved' => 'The transaction was successfully authorized.',
        'accepted' => 'The transaction has been successfully accepted into the system.',
        'decline' => 'The transaction was declined, see declinetype for further details.',
        'baddata' => 'Invalid parameters passed, see error for further details.',
        'error' => 'System error when processing the transaction, see errortype for details.',
    );

    var $_avsCodeMap = array(
        'N' => Payment_Process2::AVS_MISMATCH,
        'U' => Payment_Process2::AVS_NOAPPLY,
        'G' => Payment_Process2::AVS_NOAPPLY,
        'R' => Payment_Process2::AVS_ERROR,
        'E' => Payment_Process2::AVS_ERROR,
        'S' => Payment_Process2::AVS_ERROR,
        'O' => Payment_Process2::AVS_ERROR
    );

    var $_avsCodeMessages = array(
         'X' => 'Exact match, 9 digit zipcode.',
         'Y' => 'Exact match, 5 digit zipcode.',
         'A' => 'Street address match only.',
         'W' => '9 digit zipcode match only.',
         'Z' => '5 digit zipcode match only.',
         'N' => 'No mtach on street address or zipcode.',
         'U' => 'AVS unavailable on this card.',
         'G' => 'Non-US card issuer, AVS unavailable.',
         'R' => 'Card issuer system currently down, try again later.',
         'E' => 'Error, ineligible - not a mail/phone order.',
         'S' => 'Service not supported.',
         'O' => 'General decline or other error'
    );

    var $_cvvCodeMap = array('cvv' => Payment_Process2::CVV_MISMATCH
    );

    var $_cvvCodeMessages = array( 'cvv' => 'The CVV number is not valid.'
    );

    /**
     * @todo Good unit test coverage!
     */
    function parse()
    {
        $responseArray = array();

        parse_str(str_replace(array("\r", "\n"), "&", $this->_rawResponse), $responseArray);

        if (isset($responseArray['status'])) {
            $this->code = trim($responseArray['status']);
        }

        if (isset($responseArray['avs'])) {
            $this->avsCode = trim($responseArray['avs']);
        }

        if (isset($responseArray['transid'])) {
            $this->transactionId = trim($responseArray['transid']);
        }

        if (!isset($this->_statusCodeMessages[$this->messageCode])) {

            $message = !empty($responseArray['status']) ? $this->_statusCodeMessages[trim($responseArray['status'])] : "";
            if (!empty($responseArray['error'])) {
                $message .= "\nError type: ".$responseArray['error'].'.';
	    }

            if (!empty($responseArray['offenders'])) {
                $message .= "\nOffending fields: ".$responseArray['offenders'].'.';
            }

            $this->_statusCodeMessages[$this->messageCode] = $message;
        }
    }

}
