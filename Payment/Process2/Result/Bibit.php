<?php
require_once 'Payment/Process2/Result.php';
require_once 'XML/XPath.php';

/**
 * Payment_Process2_Bibit_Result
 *
 * @category Payment
 * @package  Payment_Process
 * @author   Robin Ericsson <lobbin@localhost.nu>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Payment_Process
 */
class Payment_Process2_Result_Bibit extends Payment_Process2_Result
{
    var $_returnCode = PAYMENT_PROCESS2_RESULT_DECLINED;

    var $_lastEvent = null;

    var $_fieldMap = array(
    );

    /**
     * Class constructor
     *
     * @param mixed $rawResponse Raw response
     */
    function __construct($rawResponse)
    {
        $this->_rawResponse = $rawResponse;
    }

    /**
     * Return error code
     *
     * @return integer
     */
    function getErrorCode()
    {
        return $this->_errorCode;
    }

    /**
     * Return response code
     *
     * @return mixed
     */
    function getCode()
    {
        return $this->_returnCode;
    }

    /**
     * Parses response
     *
     * @todo Reimplement with simplexml
     * @return void
     */
    function parse()
    {
        $doc = new XML_XPath();

        $e = $doc->load($this->_rawResponse, 'string');
        if (PEAR::isError($e)) {
            $this->_returnCode = PAYMENT_PROCESS2_RESULT_OTHER;
            $this->message     = 'Error parsing reply: '.$e->getMessage()."\n";
            return;
        }

        $e = $doc->evaluate('//reply/error/attribute::code');
        if (!PEAR::isError($e) && $e->next()) {
            $this->_returnCode = PAYMENT_PROCESS2_RESULT_OTHER;
            $this->_errorCode  = $e->getData();

            $e = $doc->evaluate('//reply/error/text()');

            $this->message = $e->getData();
            return;
        }

        $orderType = $this->_request->_data['x_action'];
        switch ($orderType) {
        case PAYMENT_PROCESS2_ACTION_BIBIT_AUTH:
            $e = $doc->evaluate('//reply/orderStatus/payment/lastEvent/text()');
            if (!PEAR::isError($e) && $e->next()) {
                $this->_lastEvent = $e->getData();
            }

            $amount = $doc->evaluate('//reply/orderStatus/payment/amount/attribute::value');
            if (!PEAR::isError($amount) && $amount->next()) {
                if ($this->_lastEvent == 'AUTHORISED') {
                    $this->_returnCode = PAYMENT_PROCESS2_RESULT_APPROVED;
                    $this->message     = '';
                    return;
                }
            }

            break;
        case PAYMENT_PROCESS2_ACTION_BIBIT_CAPTURE:
            $amount = $doc->evaluate('//reply/ok/captureReceived/amount/attribute::value');
            if (!PEAR::isError($amount) && $amount->next()) {
                $this->_returnCode = PAYMENT_PROCESS2_RESULT_APPROVED;
                return;
            }

            break;
        case PAYMENT_PROCESS2_ACTION_BIBIT_REFUND:
            $amount = $doc->evaluate('//reply/ok/refundReceived/amount/attribute::value');
            if (!PEAR::isError($amount) && $amount->next()) {
                $this->_returnCode = PAYMENT_PROCESS2_RESULT_APPROVED;
                return;
            }
            break;
        }
    }
}