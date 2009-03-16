<?php
require_once 'Payment/Process2/Result.php';


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
     * @todo Make this better - if its empty, the response code is... ?
     * @return void
     */
    function parse()
    {
        $sxe = simplexml_load_string($this->_rawResponse);

        if (!$sxe) {
            return;
        }

        $matches = $sxe->xpath('//reply/error/attribute::code');
        if (!empty($matches)) {
            $this->_returnCode = PAYMENT_PROCESS2_RESULT_OTHER;
            $this->_errorCode  = (string)$matches[0];

            $matches = $sxe->xpath('//reply/error/text()');
            if (!empty($matches)) {
                $this->message = (string)$matches[0];
            }

            return;
        }

        $orderType = $this->_request->_data['x_action'];
        switch ($orderType) {
        case Payment_Process2_Bibit::ACTION_BIBIT_AUTH:
            $matches = $sxe->xpath('//reply/orderStatus/payment/lastEvent/text()');
            if (!empty($matches)) {
                $this->_lastEvent = (string)$matches[0];
            }

            $matches = $doc->evaluate('//reply/orderStatus/payment/amount/attribute::value');
            if (!empty($matches)) {
                if ($this->_lastEvent == 'AUTHORISED') {
                    $this->_returnCode = PAYMENT_PROCESS2_RESULT_APPROVED;
                    $this->message     = '';
                    return;
                }
            }

            break;
        case Payment_Process2_Bibit::ACTION_BIBIT_CAPTURE:
            $matches = $doc->evaluate('//reply/ok/captureReceived/amount/attribute::value');
            if (!empty($matches)) {
                $this->_returnCode = PAYMENT_PROCESS2_RESULT_APPROVED;
                return;
            }

            break;
        case Payment_Process2_Bibit::ACTION_BIBIT_REFUND:
            $matches = $doc->evaluate('//reply/ok/refundReceived/amount/attribute::value');
            if (!empty($matches)) {
                $this->_returnCode = PAYMENT_PROCESS2_RESULT_APPROVED;
                return;
            }
            break;
        }
    }
}