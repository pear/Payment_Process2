<?php
require_once 'Payment/Process2/Result.php';
require_once 'Payment/Process2/Result/Driver.php';

/**
 * Payment_Process2_Result_LinkPoint
 *
 * LinkPoint result class
 *
 * @author Joe Stump <joe@joestump.net>
 * @package Payment_Process2
 */
class Payment_Process2_Result_LinkPoint extends Payment_Process2_Result implements Payment_Process2_Result_Driver
{

    var $_statusCodeMap = array('APPROVED' => Payment_Process2::RESULT_APPROVED,
                                'DECLINED' => Payment_Process2::RESULT_DECLINED,
                                'FRAUD' => Payment_Process2::RESULT_FRAUD);

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
        'YY' => Payment_Process2::AVS_MATCH,
        'YN' => Payment_Process2::AVS_MISMATCH,
        'YX' => Payment_Process2::AVS_ERROR,
        'NY' => Payment_Process2::AVS_MISMATCH,
        'XY' => Payment_Process2::AVS_MISMATCH,
        'NN' => Payment_Process2::AVS_MISMATCH,
        'NX' => Payment_Process2::AVS_MISMATCH,
        'XN' => Payment_Process2::AVS_MISMATCH,
        'XX' => Payment_Process2::AVS_ERROR
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

    var $_cvvCodeMap = array('M' => Payment_Process2::CVV_MATCH,
                             'N' => Payment_Process2::CVV_MISMATCH,
                             'P' => Payment_Process2::CVV_ERROR,
                             'S' => Payment_Process2::CVV_ERROR,
                             'U' => Payment_Process2::CVV_ERROR,
                             'X' => Payment_Process2::CVV_ERROR
    );

    var $_cvvCodeMessages = array(
        'M' => 'Card Code Match',
        'N' => 'Card code does not match',
        'P' => 'Not processed',
        'S' => 'Merchant has indicated that the card code is not present on the card',
        'U' => 'Issuer is not certified and/or has not proivded encryption keys',
        'X' => 'No response from the credit card association was received'
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

        $fieldMap = array('r_approved'  => 'code',
                           'r_error'  => 'message',
                           'r_code'  => 'approvalCode',
                           'r_ordernum'  => 'transactionId'
        );

        $xml =  new Payment_Process2_LinkPoint_XML_Parser();
        $xml->parseString('<response>'.$this->_rawResponse.'</response>');
        if (is_array($xml->response) && count($xml->response)) {
            $this->avsCode = substr($xml->response['r_avs'],0,2);
            $this->cvvCode = substr($xml->response['r_avs'],2,1);
            $this->customerId = $this->_request->customerId;
            $this->invoiceNumber = $this->_request->invoiceNumber;
 
            foreach ($fieldMap as $key => $val) {
                $this->$val = (array_key_exists($key, $xml->response))
                              ? $xml->response[$key]
                              : null;
            }


            // switch to DECLINED since a duplicate isn't *really* fraud
            if (preg_match('/duplicate/i',$this->message)) {
                $this->messageCode = 'DECLINED';
            }
        }
    }
}



require_once 'XML/Parser.php';

/**
 * Payment_Process2_LinkPoint_XML_Parser
 *
 * XML Parser for the LinkPoint response
 *
 * @todo    Split out to own class
 * @author Joe Stump <joe@joestump.net>
 * @package Payment_Process2
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
