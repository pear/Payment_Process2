<?php
require_once 'Payment/Process2/Exception.php';
require_once 'Payment/Process2/Common.php';
/**
 * Payment_Process2_Result
 *
 * The core result class that should be returned from each driver's process()
 * function. This should be exte33nded as Payment_Process2_Result_DriverName and
 * then have the appropriate fields mapped out accordingly.
 *
 * Take special care to appropriately create a parse() function in your result
 * class. You can then call _mapFields() with a resultArray (ie. exploded
 * result) to map your results from parse() into the member variables.
 *
 * Please note that this class keeps your original codes intact so they can
 * be accessed directly and then uses the function wrappers to return uniform
 * Payment_Process codes.
 *
 * @category Payment
 * @package  Payment_Process2
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Payment_Process2
 */
class Payment_Process2_Result
{
    /**
     * Processor instance which this result was instantiated from.
     *
     * This should contain a reference to the requesting Processor.
     *
     * @author Ian Eure <ieure@php.net>
     * @access private
     * @var    Object
     */
    var $_request;

    /**
     * The raw response
     *
     * @author Joe Stump <joe@joestump.net>
     * @access protected
     * @var    string  $_rawResponse
     */
    var $_rawResponse = null;

    /**
     * The approval/decline code
     *
     * The value returned by your gateway as approved/declined should be mapped
     * into this variable. Valid results should then be mapped into the
     * appropriate Payment_Process2::RESULT_* code using the $_statusCodeMap
     * array. Values returned into $code should be mapped as keys in the map
     * with Payment_Process2::RESULT_* as the values.
     *
     * @author  Joe Stump <joe@joestump.net>
     * @access  public
     * @var     mixed  $code
     * @see    Payment_Process2::RESULT_APPROVED, Payment_Process2::RESULT_DECLINED
     * @see    Payment_Process2::RESULT_OTHER, $_statusCodeMap
     */
    var $code;

    /**
     * Message/Response Code
     *
     * Along with the response (yes/no) you usually get a response/message
     * code that translates into why it was approved/declined. This is where
     * you map that code into. Your $_statusCodeMessages would then be keyed by
     * valid messageCode values.
     *
     * @author  Joe Stump <joe@joestump.net>
     * @access  public
     * @var     mixed  $messageCode
     * @see     $_statusCodeMessages
     */
    var $messageCode;

    /**
     * Message from gateway
     *
     * Map the textual message from the gateway into this variable. It is not
     * currently returned or used (in favor of the $_statusCodeMessages map, but
     * can be accessed directly for debugging purposes.
     *
     * @author  Joe Stump <joe@joestump.net>
     * @access  public
     * @var     string   $message
     * @see     $_statusCodeMessages
     */
    var $message;

    /**
     * Authorization/Approval code
     *
     * @author  Joe Stump <joe@joestump.net>
     * @access  public
     * @var     string  $approvalCode
     */
    var $approvalCode;

    /**
     * Address verification code
     *
     * The AVS code returned from your gateway. This should then be mapped to
     * the appropriate Payment_Process2::AVS_* code using $_avsCodeMap. This value
     * should also be mapped to the appropriate textual message via the
     * $_avsCodeMessages array.
     *
     * @author  Joe Stump <joe@joestump.net>
     * @access  public
     * @var     string  $avsCode
     * @see     Payment_Process2::AVS_MISMATCH, Payment_Process2::AVS_ERROR
     * @see     Payment_Process2::AVS_MATCH, Payment_Process2::AVS_NOAPPLY, $_avsCodeMap
     * @see     $_avsCodeMessages
     */
    var $avsCode;

    /**
     * Transaction ID
     *
     * This is the unique transaction ID, which is used by gateways to modify
     * transactions (credit, update, etc.). Map the appropriate value into this
     * variable.
     *
     * @author  Joe Stump <joe@joestump.net>
     * @access  public
     * @var     string $transactionId
     */
    var $transactionId;

    /**
     * Invoice Number
     *
     * Unique internal invoiceNumber (ie. your company's order/invoice number
     * that you assign each order as it is processed). It is always a good idea
     * to pass this to the gateway (which is usually then echo'd back).
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @var string $invoiceNumber
     */
    var $invoiceNumber;

    /**
     * Customer ID
     *
     * Unique internal customer ID (ie. your company's customer ID used to
     * track individual customers).
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @var string $customerId
     */
    var $customerId;

    /**
     * CVV Code
     *
     * The CVV code is the 3-4 digit number on the back of most credit cards.
     * This value should be mapped via the $_cvvCodeMap variable to the
     * appropriate Payment_Process2::CVV_* values.
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @var string $cvvCode
     */
    var $cvvCode = Payment_Process2::CVV_NOAPPLY;

    /**
     * CVV Message
     *
     * Your cvvCode value should be mapped to appropriate messages via the
     * $_cvvCodeMessage array. This value is merely here to hold the value
     * returned from the gateway (if any).
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @var string $cvvMessage
     */
    var $cvvMessage = 'No CVV message from gateway';


    var $_avsCodeMap = array();

    var $_cvvCodeMap = array();

    /**
     * Class constructor
     *
     * @param string $rawResponse Raw response
     * @param Payment_Process2_Common $request     Request
     */
    function __construct($rawResponse,  Payment_Process2_Common $request)
    {
        $this->_rawResponse = $rawResponse;
        $this->_request     = $request;
    }


    /**
    * factory
    *
    * @param string $type        Type
    * @param string $rawResponse Raw response
    * @param  Payment_Process2_Common $request     Request
    *
    * @return Payment_Process2_Result
    * @throws Payment_Process2_Exception
    * @author Joe Stump <joe@joestump.net>
    * @author Ian Eure <ieure@php.net>
    */
    function factory($type, $rawResponse, Payment_Process2_Common $request)
    {


        $class = 'Payment_Process2_Result_'.$type;

        $path = 'Payment/Process2/Result/'.$type.'.php';
        if (@fclose(@fopen($path, 'r', true))) {
            include_once $path;

            if (class_exists($class)) {
                $ret = new $class($rawResponse, $request);
                return $ret;
            }
        }

        throw new Payment_Process2_Exception('Invalid response type: '.$type.'('.$class.')');
    }

    /**
     * validate
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @return mixed
     */
    function validate()
    {
        $request = $this->_request;
        $payment = $request->_payment;

        if ($request->getOption('avsCheck') === true) {
            if ($this->getAVSCode() != Payment_Process2::AVS_MATCH) {
                throw new Payment_Process2_Exception('AVS check failed',
                                        Payment_Process2::ERROR_AVS);
            }
        }

        if ($request->getOption('cvvCheck') === true &&
            $payment instanceof Payment_Process2_Type_CreditCard) {

            if ($this->getCvvCode() != Payment_Process2::CVV_MATCH) {
                throw new Payment_Process2_Exception('CVV check failed',
                                        Payment_Process2::ERROR_CVV);
            }

        }

        if ($this->getCode() != Payment_Process2::RESULT_APPROVED) {
            throw new Payment_Process2_Exception($this->getMessage(),
                                    Payment_Process2::RESULT_DECLINED);
        }

        return true;
    }

    /**
     * getCode
     *
     * @return integer one of Payment_Process2::RESULT_* constant
     * @author Joe Stump <joe@joestump.net>
     * @access public
     */
    function getCode()
    {
        if (isset($this->_statusCodeMap[$this->code])) {
            return $this->_statusCodeMap[$this->code];
        }

        return Payment_Process2::RESULT_DECLINED;
    }

    /**
     * getMessage
     *
     * Return the message from the code map, or return the raw message if
     * there is one. Otherwise, return a worthless message.
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @return string
     */
    function getMessage()
    {
        if (isset($this->_statusCodeMessages[$this->messageCode])) {
            return $this->_statusCodeMessages[$this->messageCode];
        } elseif (strlen($this->message)) {
            return $this->message;
        }

        return 'No message reported';
    }

    /**
     * Returns the AVS code
     *
     * @return integer one of Payment_Process2::AVS_* constants
     */
    function getAVSCode()
    {
        return isset($this->_avsCodeMap[$this->avsCode]) ? $this->_avsCodeMap[$this->avsCode] : null;
    }

    /**
     * Returns the AVS message
     *
     * @return string
     */
    function getAVSMessage()
    {
        return isset($this->_avsCodeMessages[$this->avsCode]) ? $this->_avsCodeMessages[$this->avsCode] : null;
    }


    /**
     * Return the CVV match code
     *
     * @todo Think if this should raise exceptions for unknown cvvcode?
     *
     * @return integer One of Payment_Process2::CVV_* constants or null
     */
    function getCvvCode()
    {
        return isset($this->_cvvCodeMap[$this->cvvCode])? $this->_cvvCodeMap[$this->cvvCode] : null;
    }

    /**
     * Returns the CVV match message
     *
     * @return string
     */
    function getCvvMessage()
    {
        return $this->_cvvCodeMessages[$this->cvvCode];
    }

    /**
     * Accept an object
     *
     * @param object $object Object to accept
     *
     * @return boolean  TRUE if accepted, FALSE otherwise
     */
    function accept($object)
    {
        if (is_a($object, 'Log')) {
            $this->_log = $object;
            return true;
        }
        return false;
    }

    /**
     * Log a message
     *
     * @param string $message  Message to log
     * @param string $priority Message priority
     *
     * @return mixed  Return value of Log::log(), or false if no Log instance
     *                has been accepted.
     */
    function log($message, $priority = null)
    {
        if (isset($this->_log) && is_object($this->_log)) {
            return $this->_log->log($message, $priority);
        }
        return false;
    }
}

