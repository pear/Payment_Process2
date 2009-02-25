<?php
require_once 'Payment/Process2/Result.php';
require_once 'Payment/Process2/Result/Driver.php';

class Payment_Process2_Result_Paycom extends Payment_Process2_Result implements Payment_Process2_Result_Driver
{

    // {{{ properties
    /**
    * $_statusCodeMap
    *
    * @author Joe Stump <joe@joestump.net>
    * @access protected
    */
    var $_statusCodeMap = array('approved' => PAYMENT_PROCESS2_RESULT_APPROVED,
                                'declined' => PAYMENT_PROCESS2_RESULT_DECLINED,
                                'error' => PAYMENT_PROCESS2_RESULT_OTHER,
                                'test' => PAYMENT_PROCESS2_RESULT_OTHER);

    /**
    * Paycom status codes
    *
    * @author Joe Stump <joe@joestump.net>
    * @access protected
    * @see getStatusText()
    */
    var $_statusCodeMessages = array(
          'approved' => 'This transaction has been approved.',
          'declined' => 'This transaction has been declined.',
          'error' => 'This transaction has encountered an error.',
          'test' => 'This transaction is a test.'
    );

    var $_avsCodeMap = array(
        'A' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'N' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'R' => PAYMENT_PROCESS2_AVS_ERROR,
        'S' => PAYMENT_PROCESS2_AVS_ERROR,
        'G' => PAYMENT_PROCESS2_AVS_ERROR,
        'U' => PAYMENT_PROCESS2_AVS_ERROR,
        'W' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'X' => PAYMENT_PROCESS2_AVS_MATCH,
        'Y' => PAYMENT_PROCESS2_AVS_MATCH,
        'Z' => PAYMENT_PROCESS2_AVS_MISMATCH
    );

    var $_avsCodeMessages = array(
        'A' => 'Address matches, ZIP does not',
        'N' => 'Address and zip do not match',
        'R' => 'Retry - System unavailable or timeout',
        'S' => 'Retry - System unavailable or timeout',
        'G' => 'Retry - System unavailable or timeout',
        'U' => 'Address information unavailable (usually foreign issuing bank)',
        'W' => '9-digit zip matches, Address (street) does not',
        'X' => 'Address and 9-digit zip match',
        'Y' => 'Address and 5-digit zip match',
        'Z' => '5-digit zip matches, Address (street) does not'
    );

    var $_cvvCodeMap = array('E' => PAYMENT_PROCESS2_CVV_ERROR);

    var $_cvvCodeMessages = array(
        'E' => 'Paycom module does not support CVV checks'
    );
    // }}}

    // {{{ parse()
    /**
    * parse
    *
    * @author Joe Stump <joe@joestump.net>
    * @access public
    * @return void
    * @see Payemnt_Process_Paycom::process()
    */
    function parse()
    {
        $parts = explode('|',trim($this->_rawResponse));

        foreach ($parts as $part) {
            list($var,$val) = explode('=',$part);
            $$var = trim($val);
        }

        $response = explode(',',$response);

        $this->code = $status;
        $this->messageCode = $status;
        $this->approvalCode = substr($response[0],1,strlen($response[1]));

        if ($this->getCode() == PAYMENT_PROCESS2_RESULT_APPROVED) {
            $this->avsCode = substr($response[0],7,1);
        } else {
            $this->avsCode = 'R'; // Default to error
        }

        if (isset($auth_idx)) {
            $this->transactionId = $auth_idx;
        } elseif (isset($order_idx)) {
            $this->transactionId = $order_idx;
        }

        $this->cvvCode = 'E'; // Not supported
    }
    // }}}

}
