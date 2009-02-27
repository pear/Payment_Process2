<?php
require_once 'Payment/Process2/Result.php';
require_once 'Payment/Process2/Result/Driver.php';

class Payment_Process2_Result_TrustCommerce extends Payment_Process2_Result implements Payment_Process2_Result_Driver
{

    var $_statusCodeMap = array('approved' => PAYMENT_PROCESS2_RESULT_APPROVED,
                                'accepted' => PAYMENT_PROCESS2_RESULT_APPROVED,
                                'declined' => PAYMENT_PROCESS2_RESULT_DECLINED,
                                'baddata' => PAYMENT_PROCESS2_RESULT_OTHER,
                                'error' => PAYMENT_PROCESS2_RESULT_OTHER);

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
        'N' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'U' => PAYMENT_PROCESS2_AVS_NOAPPLY,
        'G' => PAYMENT_PROCESS2_AVS_NOAPPLY,
        'R' => PAYMENT_PROCESS2_AVS_ERROR,
        'E' => PAYMENT_PROCESS2_AVS_ERROR,
        'S' => PAYMENT_PROCESS2_AVS_ERROR,
        'O' => PAYMENT_PROCESS2_AVS_ERROR
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

    var $_cvvCodeMap = array('cvv' => PAYMENT_PROCESS2_CVV_MISMATCH
    );

    var $_cvvCodeMessages = array( 'cvv' => 'The CVV number is not valid.'
    );

    var $_fieldMap = array('status'  => 'code',
                           'avs'  => 'avsCode',
                           'transid'  => 'transactionId'
    );

    /**
     * @todo Good unit test coverage!
     */
    function parse()
    {
      $array = preg_split("/\n/",$this->_rawResponse,0,PREG_SPLIT_NO_EMPTY);
      $responseArray = array();
      for($i=0;$i<sizeof($array);$i++)
      {
          $response_line = $array[$i];
          $response_array = preg_split("/=/",$response_line);
          $key = $response_array[0];
          $value = $response_array[1];
          $responseArray[$key] = $value;
      }
      $this->_mapFields($responseArray);
    }

    /**
     * @todo Good unit test coverage!
     */
    function _mapFields($responseArray)
    {
        if (empty($responseArray)) {
            return;
        }

        foreach ($this->_fieldMap as $key => $val) {
            if (isset($responseArray[$key])) {
                $this->$val = $responseArray[$key];
            }
        }
        if (!isset($this->_statusCodeMessages[$this->messageCode]))
        {
            $message = $this->_statusCodeMessages[$responseArray['status']];
            if($responseArray['error'])
            {
                $message .= "\nError type: ".$responseArray['error'].'.';
                if($responseArray['offenders'])
                {
                    $message .= "\nOffending fields: ".$responseArray['offenders'].'.';
                }
            }
            $this->_statusCodeMessages[$this->messageCode] = $message;
        }
    }

}