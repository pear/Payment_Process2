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
require_once 'Payment/Process2/Result/Transfirst.php';
require_once 'Net/Curl.php';

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
//         'name'              => "cardHolderName",
        'address'           => "cardHolderAddress",
        'city'              => "cardHolderCity",
        'state'             => "cardHolderState",
        'phone'             => "cardHolderPhone",
        'email'             => "cardHolderEmail"
    );

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
        if ($this->_options['testTransaction']) {
            $this->_data['testTransaction'] = $this->_options['testTransaction'];
        }
        $this->_handleCardHolderName();
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
        if(PEAR::isError($res = $this->validate())) {
            return($res);
        }

        // Prepare the data
        $this->_prepare();

        // Don't die partway through
        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);

        $req = new Net_Curl($this->_options['authorizeUri']);
        if (PEAR::isError($req)) {
            PEAR::popErrorHandling();
            return $req;
        }
        $req->type = 'POST';
        $req->fields = $this->_prepareQueryString();
        $req->userAgent = 'PEAR Payment_Process2_Transfirst 0.1';
        $res = $req->execute();
        $req->close();
        if (PEAR::isError($res)) {
            PEAR::popErrorHandling();
            return $res;
        }

        $this->_processed = true;

        // Restore error handling
        PEAR::popErrorHandling();

        $response = trim($res);
        print "Response: {$response}\n";
        $result = Payment_Process2_Result::factory('Transfirst', $response);
        $result->_request = $this;
        $this->_result = $result;

        return $result;

        /*
         * HTTP_Request doesn't do SSL until PHP 4.3.0, but it
         * might be useful later...
        $req = new HTTP_Request($this->_authUri);
        $this->_setPostData();
        $req->sendRequest();
        */
    }

    /**
     * Get (completed) transaction status.
     *
     * @return string Two-digit status returned from gateway.
     */
    function getStatus()
    {
        if (!$this->_processed) {
            return PEAR::raiseError('The transaction has not been processed yet.', PAYMENT_PROCESS2_ERROR_INCOMPLETE);
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
            return PEAR::raiseError('The transaction has not been processed yet.', PAYMENT_PROCESS2_ERROR_INCOMPLETE);
        }
        return $this->_result->_sequenceNumber;
    }

    /**
     * Prepare the POST query string.
     *
     * @access private
     * @return string The query string
     */
    function _prepareQueryString()
    {
        foreach($this->_data as $var => $value) {
            if (strlen($value))
                $tmp[] = urlencode($var).'='.urlencode($value);
        }
        return @implode('&', $tmp);
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
     * Map firstName & lastName
     *
     * P_P now has split firstName/lastName fields, instead of 'name.' This
     * handles concatenating them into the Transfirst cardHolderName field.
     *
     * @return  void
     */
    function _handleCardHolderName()
    {
        $this->_data['cardHolderName'] = $this->firstName . ' ' . $this->lastName;
    }

    /**
     * Validate the merchant account login.
     *
     * The Transfirst docs specify that the login is exactly eight digits.
     *
     * @access private
     * @return boolean true if valid, false otherwise
     */
    function _validateLogin()
    {
        return Validate::string($this->login, array(
            'format' => VALIDATE_NUM,
            'max_length' => 8,
            'min_length' => 8
        ));
    }

    /**
     * Validate the merchant account password.
     *
     * The Transfirst docs specify that the password is a string between 6 and 10
     * characters in length.
     *
     * @access private
     * @return boolean true if valid, false otherwise
     */
    function _validatePassword()
    {
        return Validate::string($this->password, array(
            'format' => VALIDATE_ALPHA . VALIDATE_NUM,
            'min_length' => 6,
            'max_length' => 10
        ));
    }

    /**
     * Validate the invoice number.
     *
     * Invoice number must be a 5-character long alphanumeric string.
     *
     * @return boolean true on success, false otherwise
     */
    function _validateInvoiceNumber()
    {
        return Validate::string($this->invoiceNumber, array(
            'format' => VALIDATE_NUM . VALIDATE_ALPHA,
            'min_length' => 5,
            'max_length' => 5
        ));
    }

    /**
     * Validate the invoice number.
     *
     * Invoice no. must be a 15-character long alphanumeric string.
     *
     * @return boolean true on success, false otherwise
     */
    function _validateCustomerId()
    {
        return Validate::string($this->customerId, array(
            'format' => VALIDATE_NUM . VALIDATE_ALPHA,
            'min_length' => 15,
            'max_length' => 15
        ));
    }

    /**
     * Validate the zip code.
     *
     * Zip is only required if AVS is enabled.
     *
     * @return boolean true on success, false otherwise.
     */
    function _validateZip()
    {
        if(strlen($this->zip) || $this->performAvs) {
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
