<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Transfirst processor
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. The name of the authors may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHORS ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
 * EVENT SHALL THE AUTHORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Payment
 * @package   Payment_Process
 * @author    Ian Eure <ieure@php.net>
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process
 */

require_once 'Payment/Process2/Common.php';
require_once 'Payment/Process2/Driver.php';
require_once 'Payment/Process2/Exception.php';
require_once 'Payment/Process2/Result/Transfirst.php';
require_once 'HTTP/Request2.php';
require_once 'Validate.php';


// Transfirst transaction types
// Request authorization only - no funds are transferred.
define('PAYMENT_PROCESS2_ACTION_TRANSFIRST_AUTH', 30);
// Transfer funds from a previous authorization.
define('PAYMENT_PROCESS2_ACTION_TRANSFIRST_SETTLE', 40);
// Authorize & transfer funds
define('PAYMENT_PROCESS2_ACTION_TRANSFIRST_AUTHSETTLE', 32);
// Debit the indicated amount to a previously-charged card.
define('PAYMENT_PROCESS2_ACTION_TRANSFIRST_CREDIT', 20);
// Cancel authorization
define('PAYMENT_PROCESS2_ACTION_TRANSFIRST_VOID', 61);

define('PAYMENT_PROCESS2_RESULT_TRANSFIRST_APPROVAL', 00);
define('PAYMENT_PROCESS2_RESULT_TRANSFIRST_DECLINE', 05);
define('PAYMENT_PROCESS2_RESULT_TRANSFIRST_INVALIDAMOUNT', 13);
define('PAYMENT_PROCESS2_RESULT_TRANSFIRST_INVALIDCARDNO', 14);
define('PAYMENT_PROCESS2_RESULT_TRANSFIRST_REENTER', 19);


/**
 * Payment_Process2_Transfirst
 *
 * This is a processor for TransFirst's merchant payment gateway, formerly known
 * as DPILink. (http://www.transfirst.com/)
 *
 * *** WARNING ***
 * This is BETA code. While I have tested it and it appears to work for me, I
 * strongly recommend that you do additional testing before using it in
 * production systems.
 *
 * @package Payment_Process
 * @author Ian Eure <ieure@php.net>
 * @version @version@
 */
class Payment_Process2_Transfirst extends Payment_Process2_Common implements Payment_Process2_Driver
{
    /**
     * Front-end -> back-end field map.
     *
     * This array contains the mapping from front-end fields (defined in
     * the Payment_Process class) to the field names Transfirst requires.
     *
     * @see _prepare()
     * @access private
     */
    var $_fieldMap = array(
        // Required
        'login'             => "DPIAccountNum",
        'password'          => "password",
        'action'            => "transactionCode",
        'invoiceNumber'     => "orderNum",
        'customerId'        => "customerNum",
        'amount'            => "transactionAmount",
        'transactionSource' => "ECommerce",
        // Credit Card Type
        'cardNumber'        => "cardAccountNum",
        'expDate'           => "expirationDate",
        'zip'               => "cardHolderZip",
        // Common Type
        'name'              => "cardHolderName",
        'address'           => "cardHolderAddress",
        'city'              => "cardHolderCity",
        'state'             => "cardHolderState",
        'phone'             => "cardHolderPhone",
        'email'             => "cardHolderEmail"
    );

    /** @todo Work out if this actually lives in credit card */
    var $expDate = null;

    /**
     * Default options for this processor.
     *
     * @see Payment_Process::setOptions()
     * @access private
     */
    var $_defaultOptions = array(
        'authorizeUri' => "https://epaysecure.transfirst.com/eLink/authpd.asp"
    );

    /**
     * Has the transaction been processed?
     *
     * @type boolean
     * @access private
     */
    var $_processed = false;

    /**
     * Constructor.
     *
     * @param  array  $options  Class options to set.
     * @see Payment_Process::setOptions()
     * @return void
     */
    function __construct($options = array(), HTTP_Request2 $request = null)
    {
        parent::__construct($options, $request);
        $this->_driver = 'Transfirst';
        $this->_makeRequired('login', 'password', 'action', 'invoiceNumber', 'customerId', 'amount', 'cardNumber', 'expDate');
    }

    /**
     * Prepare the data.
     *
     * This function handles the 'testTransaction' option, which is specific to
     * this processor.
     */
    function _prepare()
    {
        if (!empty($this->_options['testTransaction'])) {
            $this->_data['testTransaction'] = $this->_options['testTransaction'];
        }

        return parent::_prepare();
    }

    /**
     * Process the transaction.
     *
     * @return mixed Payment_Process2_Result on success, PEAR_Error on failure
     */
    function process()
    {
        // Sanity check
        $this->validate();

        // Prepare the data
        $this->_prepare();

        $req = clone $this->_request;
        $req->setURL($this->_options['authorizeUri']);
        $req->setMethod('POST');
        $req->addPostParameter($this->prepareRequestData());

        $res = $req->send();

        $this->_processed = true;

        $response = trim($res->getBody());
        $result = Payment_Process2_Result::factory('Transfirst', $response, $this);
        $result->_request = $this;
        $this->_result = $result;

        return $result;
    }

    /**
     * Get (completed) transaction status.
     *
     * @return string Two-digit status returned from gateway.
     */
    function getStatus()
    {
        if (!$this->_processed) {
            throw new Payment_Process2_Exception('The transaction has not been processed yet.', PAYMENT_PROCESS2_ERROR_INCOMPLETE);
        }
        return $this->_result->code;
    }

    /**
     * Get transaction sequence.
     *
     * 'Sequence' is what Transfirst calls their transaction ID/approval code. This
     * function returns that code from a processed transaction.
     *
     * @return mixed  Sequence ID, or PEAR_Error if the transaction hasn't been
     *                processed.
     */
    function getSequence()
    {
        if (!$this->_processed) {
            throw new Payment_Process2_Exception('The transaction has not been processed yet.', PAYMENT_PROCESS2_ERROR_INCOMPLETE);
        }
        return $this->_result->_sequenceNumber;
    }

    function prepareRequestData()
    {
        $data = array();
        foreach ($this->_data as $var => $value) {
            if (!empty($value)) {
                $data[$var] = $value;
            }
        }
        return $data;
    }

    /**
     * Handle transaction source.
     *
     * @access private
     */
    function _handleTransactionSource()
    {
        $specific = $this->_fieldMap['transactionSource'];
        if ($this->transactionSource == PAYMENT_PROCESS2_SOURCE_ONLINE) {
            $this->_data[$specific] = 'Y';
        } else {
            $this->_data[$specific] = 'N';
        }
    }

    /**
     * Handle card expiration date.
     *
     * The gateway wants the date in the format MMYY, with no other chars.
     *
     * @access private
     */
    function _handleExpDate()
    {
        $specific = $this->_fieldMap['expDate'];
        if (isset($this->_data[$specific])) {
            $this->_data[$specific] = str_replace('/', '', $this->_data[$specific]);
        } else {
            $this->_data[$specific] = str_replace('/', '', $this->expDate);
        }
    }

    /**
     * Validate the merchant account login.
     *
     * The Transfirst docs specify that the login is exactly eight digits.
     *
     * @access private
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validateLogin()
    {
        $options = array(
            'format' => VALIDATE_NUM,
            'max_length' => 8,
            'min_length' => 8
        );

        if (!Validate::string($this->login, $options)) {
            throw new Payment_Process2_Exception("Invalid login");
        }

        return true;
    }

    /**
     * Validate the merchant account password.
     *
     * The Transfirst docs specify that the password is a string between 6 and 10
     * characters in length.
     *
     * @access private
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validatePassword()
    {
        $options = array(
            'format' => VALIDATE_ALPHA . VALIDATE_NUM,
            'min_length' => 6,
            'max_length' => 10
        );

        if (!Validate::string($this->password, $options)) {
            throw new Payment_Process2_Exception("Invalid password");
        }

        return true;
    }

    /**
     * Validate the invoice number.
     *
     * Invoice number must be a 5-character long alphanumeric string.
     *
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validateInvoiceNumber()
    {
        $options = array(
            'format' => VALIDATE_NUM . VALIDATE_ALPHA,
            'min_length' => 5,
            'max_length' => 5
        );

        if (!Validate::string($this->invoiceNumber, $options)) {
            throw new Payment_Process2_Exception("Invalid invoiceNumber");
        }

        return true;
    }

    /**
     * Validate the customer id
     *
     * Customer id must be a 15-character long alphanumeric string.
     *
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validateCustomerId()
    {

        $options = array(
            'format' => VALIDATE_NUM . VALIDATE_ALPHA,
            'min_length' => 15,
            'max_length' => 15
        );

        if (!Validate::string($this->customerId, $options)) {
            throw new Payment_Process2_Exception("Invalid customerId");
        }

        return true;
    }

    /**
     * Validate the zip code.
     *
     * Zip is only required if AVS is enabled.
     *
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validateZip()
    {
        if (strlen($this->zip) || $this->performAvs) {
            return parent::_validateZip();
        }
        return true;
    }

    public function translateAction($action) {
        switch ($action) {
            case PAYMENT_PROCESS2_ACTION_NORMAL:
                return PAYMENT_PROCESS2_ACTION_TRANSFIRST_AUTHSETTLE;

            case PAYMENT_PROCESS2_ACTION_AUTHONLY:
                return PAYMENT_PROCESS2_ACTION_TRANSFIRST_AUTH;

            case PAYMENT_PROCESS2_ACTION_POSTAUTH:
                return PAYMENT_PROCESS2_ACTION_TRANSFIRST_SETTLE;
        }
        return false;
    }
}

?>
