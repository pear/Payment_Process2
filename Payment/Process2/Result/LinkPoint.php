<?php
require_once 'Payment/Process2/Result.php';

/**
 * Payment_Process2_Result_LinkPoint
 *
 * LinkPoint result class
 *
 * @author Joe Stump <joe@joestump.net>
 * @package Payment_Process
 */
class Payment_Process2_Result_LinkPoint extends Payment_Process2_Result
{

    var $_statusCodeMap = array('APPROVED' => PAYMENT_PROCESS2_RESULT_APPROVED,
                                'DECLINED' => PAYMENT_PROCESS2_RESULT_DECLINED,
                                'FRAUD' => PAYMENT_PROCESS2_RESULT_FRAUD);

    /**
     * LinkPoint status codes
     *
     * This array holds many of the common response codes. There are over 200
     * response codes - so check the LinkPoint manual if you get a status
     * code that does not match (see "Response Reason Codes & Response
     * Reason Text" in the AIM manual).
     *
     * @see getStatusText()
     * @access private
     */
    var $_statusCodeMessages = array(
        'APPROVED' => 'This transaction has been approved.',
        'DECLINED' => 'This transaction has been declined.',
        'FRAUD' => 'This transaction has been determined to be fraud.');

    var $_avsCodeMap = array(
        'YY' => PAYMENT_PROCESS2_AVS_MATCH,
        'YN' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'YX' => PAYMENT_PROCESS2_AVS_ERROR,
        'NY' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'XY' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'NN' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'NX' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'XN' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'XX' => PAYMENT_PROCESS2_AVS_ERROR
    );

    var $_avsCodeMessages = array(
        'YY' => 'Address matches, zip code matches',
        'YN' => 'Address matches, zip code does not match',
        'YX' => 'Address matches, zip code comparison not available',
        'NY' => 'Address does not match, zip code matches',
        'XY' => 'Address comparison not available, zip code matches',
        'NN' => 'Address comparison does not match, zip code does not match',
        'NX' => 'Address does not match, zip code comparison not available',
        'XN' => 'Address comparison not available, zip code does not match',
        'XX' => 'Address comparison not available, zip code comparison not available'
    );

    var $_cvvCodeMap = array('M' => PAYMENT_PROCESS2_CVV_MATCH,
                             'N' => PAYMENT_PROCESS2_CVV_MISMATCH,
                             'P' => PAYMENT_PROCESS2_CVV_ERROR,
                             'S' => PAYMENT_PROCESS2_CVV_ERROR,
                             'U' => PAYMENT_PROCESS2_CVV_ERROR,
                             'X' => PAYMENT_PROCESS2_CVV_ERROR
    );

    var $_cvvCodeMessages = array(
        'M' => 'Card Code Match',
        'N' => 'Card code does not match',
        'P' => 'Not processed',
        'S' => 'Merchant has indicated that the card code is not present on the card',
        'U' => 'Issuer is not certified and/or has not proivded encryption keys',
        'X' => 'No response from the credit card association was received'
    );

    var $_fieldMap = array('r_approved'  => 'code',
                           'r_error'  => 'message',
                           'r_code'  => 'approvalCode',
                           'r_ordernum'  => 'transactionId'
    );

    /**
    * parse
    *
    * @author Joe Stump <joe@joestump.net>
    * @access public
    * @return void
    */
    function parse()
    {
        $xml =  new Payment_Processor_LinkPoint_XML_Parser();
        $xml->parseString('<response>'.$this->_rawResponse.'</response>');
        if (is_array($xml->response) && count($xml->response)) {
            $this->avsCode = substr($xml->response['r_avs'],0,2);
            $this->cvvCode = substr($xml->response['r_avs'],2,1);
            $this->customerId = $this->_request->customerId;
            $this->invoiceNumber = $this->_request->invoiceNumber;
            $this->_mapFields($xml->response);

            // switch to DECLINED since a duplicate isn't *really* fraud
            if(eregi('duplicate',$this->message)) {
                $this->messageCode = 'DECLINED';
            }
        }
    }
}



require_once 'XML/Parser.php';

/**
 * Payment_Processor_LinkPoint_XML_Parser
 *
 * XML Parser for the LinkPoint response
 *
 * @todo    Split out to own class
 * @author Joe Stump <joe@joestump.net>
 * @package Payment_Process
 */
class Payment_Process2_LinkPoint_XML_Parser extends XML_Parser
{
    /**
     * $response
     *
     * @var array $response Raw response as an array
     * @access public
     */
    var $response = array();

    /**
     * $log
     *
     * @var string $tag Current tag
     * @access private
     */
    var $tag = null;

    /**
     * startHandler
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @param resource $xp XML processor handler
     * @param string $elem Name of XML entity
     * @return void
     */
    function startHandler($xp, $elem, $attribs)
    {
        $this->tag = $elem;
    }

    /**
     * endHandler
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @param resource $xp XML processor handler
     * @param string $elem Name of XML entity
     * @return void
     */
    function endHandler($xp, $elem)
    {

    }

    /**
     * defaultHandler
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @param resource $xp XML processor handler
     * @param string $data
     * @return void
     */
    function defaultHandler($xp,$data)
    {
        $this->response[strtolower($this->tag)] = $data;
    }
}