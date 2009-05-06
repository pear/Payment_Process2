<?php

require_once 'Payment/Process2/Result.php';

class Payment_Process2_Result_ANZ extends Payment_Process2_Result {

    var $_statusCodeMap = array(
        '0' => Payment_Process2::RESULT_APPROVED,
        '1' => Payment_Process2::RESULT_OTHER,
        '2' => Payment_Process2::RESULT_DECLINED,
        '3' => Payment_Process2::RESULT_OTHER,
        '4' => Payment_Process2::RESULT_DECLINED,
        '5' => Payment_Process2::RESULT_DECLINED,
        '6' => Payment_Process2::RESULT_OTHER,
        '7' => Payment_Process2::RESULT_DECLINED,
        '8' => Payment_Process2::RESULT_DECLINED,
        '9' => Payment_Process2::RESULT_DECLINED,
    );

    var $_statusCodeMessages = array(
        '0' => 'Transaction approved',
        '1' => 'Transaction could not be processed',
        '2' => 'Transaction declined - contact issuing bank',
        '3' => 'No reply from Processing Host',
        '4' => 'Card has expired',
        '5' => 'Insufficient credit',
        '6' => 'Error communicating with Bank',
        '7' => 'Message detail error',
        '8' => 'Transaction declined - transaction type not supported',
        '9' => 'Bank declined transaction - do not contact bank',
    );

    var $_avsCodeMap = array(
    );

    var $_avsCodeMessages = array(
    );

    var $_cvvCodeMap = array(
    );

    var $_cvvCodeMessages = array(
    );

    var $_fieldMap = array('vpc_TxnResponseCode' => 'code',
                           'vpc_TransactionNo' => 'transactionId',
                           'vpc_Message' => 'message',
                           'vpc_CSCResultCode' => 'cvvCheck',
                           'vpc_ReceiptNo' => 'receiptNumber'
    );

    var $receiptNumber;

    /**
     * Class constructor
     *
     * @param string $rawResponse Raw response
     * @param mixed  $request     Request
     */
    function __construct($rawResponse, $request)
    {
        $this->_rawResponse = $rawResponse;
        $this->_request     = $request;
    }

    function parse()
    {
        parse_str($this->_rawResponse, $responseArray);
        $this->_mapFields($responseArray);
    }
}
