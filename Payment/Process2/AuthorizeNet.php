<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Authorize.Net processor
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
 * @author    Joe Stump <joe@joestump.net>                                |
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright 1997-2008 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process
 */

require_once 'Payment/Process2.php';
require_once 'Payment/Process2/Common.php';
require_once 'Payment/Process2/Driver.php';
require_once 'Payment/Process2/Result/AuthorizeNet.php';
require_once 'HTTP/Request2.php';

/**
 * Payment_Process2_AuthorizeNet
 *
 * This is a processor for Authorize.net's merchant payment gateway.
 * (http://www.authorize.net/)
 *
 * *** WARNING ***
 * This is BETA code, and has not been fully tested. It is not recommended
 * that you use it in a production environment without further testing.
 *
 * @package    Payment_Process
 * @author     Joe Stump <joe@joestump.net>
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @version    @version@
 * @link       http://www.authorize.net/
 */
class Payment_Process2_AuthorizeNet extends Payment_Process2_Common implements Payment_Process2_Driver
{
    /**
     * Front-end -> back-end field map.
     *
     * This array contains the mapping from front-end fields (defined in
     * the Payment_Process class) to the field names Authorize.Net requires.
     *
     * @see _prepare()
     * @access private
     */
    var $_fieldMap = array(
        // Required
        'login'         => 'x_login',
        'password'      => 'x_password',
        'action'        => 'x_type',

        // Optional
        'login'         => 'x_login',
        'invoiceNumber' => 'x_invoice_num',
        'customerId'    => 'x_cust_id',
        'amount'        => 'x_amount',
        'description'   => 'x_description',
        'name'          => '',
        'postalCode'    => 'x_zip',
        'zip'           => 'x_zip',
        'company'       => 'x_company',
        'address'       => 'x_address',
        'city'          => 'x_city',
        'state'         => 'x_state',
        'country'       => 'x_country',
        'phone'         => 'x_phone',
        'email'         => 'x_email',
        'ip'            => 'x_customer_ip',
    );

    /**
     * $_typeFieldMap
     *
     * @author Joe Stump <joe@joestump.net>
     * @access protected
     */
    var $_typeFieldMap = array(

           'CreditCard' => array(
                'firstName'  => 'x_first_name',
                'lastName'   => 'x_last_name',
                'cardNumber' => 'x_card_num',
                'cvv'        => 'x_card_code',
                'expDate'    => 'x_exp_date'
           ),

           'eCheck' => array(
                'routingCode'   => 'x_bank_aba_code',
                'accountNumber' => 'x_bank_acct_num',
                'type'          => 'x_bank_acct_type',
                'bankName'      => 'x_bank_name'
           )
    );

    /**
     * Default options for this processor.
     *
     * @see Payment_Process::setOptions()
     * @access private
     */
    var $_defaultOptions = array(
         'authorizeUri' => 'https://secure.authorize.net/gateway/transact.dll',
         'x_delim_data' => 'TRUE',
         'x_delim_char' => ',',
         'x_encap_char' => '|',
         'x_relay'      => 'FALSE',
         'x_email_customer' => 'FALSE',
         'x_currency_code'  => 'USD',
         'x_version'        => '3.1'
    );

    /**
     * List of possible encapsulation characters
     *
     * @var string
     * @access private
     */
    var $_encapChars = '|~#$^*_=+-`{}![]:";<>?/&';

    /**
     * Has the transaction been processed?
     *
     * @type boolean
     * @access private
     */
    var $_processed = false;

    /**
     * The response body sent back from the gateway.
     *
     * @access private
     */
    var $_responseBody = '';

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
        $this->_driver = 'AuthorizeNet';
        $this->_makeRequired('login', 'password', 'action');
    }

    /**
     * Processes the transaction.
     *
     * Success here doesn't mean the transaction was approved. It means
     * the transaction was sent and processed without technical difficulties.
     *
     * @return mixed Payment_Process2_Result on success, PEAR_Error on failure
     * @access public
     */
    function process()
    {
        // Sanity check
        $result = $this->validate();
        if (PEAR::isError($result)) {
            return $result;
        }

        // Prepare the data
        $result = $this->_prepare();
        if (PEAR::isError($result)) {
            return $result;
        }

        $request = clone $this->_request;
        $request->setURL($this->_options['authorizeUri']);


        $request->setMethod('post');
        $request->addPostParameter($this->prepareRequestData());


        $result = $request->send();

        $this->_responseBody = trim($result->getBody());
        $this->_processed = true;

        $response = Payment_Process2_Result::factory($this->_driver,
                                                     $this->_responseBody,
                                                     $this);

        if (!PEAR::isError($response)) {
            $response->parse();

            $r = $response->isLegitimate();
            if (PEAR::isError($r)) {
                return $r;
            } elseif ($r === false) {
                return PEAR::raiseError('Illegitimate response from gateway');
            }
        }
        $response->action = $this->action;

        return $response;
    }

    /**
     * Processes a callback from payment gateway
     *
     * Success here doesn't mean the transaction was approved. It means
     * the callback was received and processed without technical difficulties.
     *
     * @return mixed Payment_Process2_Result on success, PEAR_Error on failure
     */
    function processCallback()
    {
        $this->_responseBody = $_POST;
        $this->_processed = true;

        $response = Payment_Process2_Result::factory($this->_driver,
                            $this->_responseBody);
        if (!PEAR::isError($response)) {
            $response->_request =& $this;
            $response->parseCallback();

            $r = $response->isLegitimate();
            if (PEAR::isError($r)) {
                return $r;

            } elseif ($r === false) {
                return PEAR::raiseError('Illegitimate callback from gateway.');
            }
        }

        return $response;
    }

    /**
     * Get (completed) transaction status.
     *
     * @return string Two-digit status returned from gateway.
     */
    function getStatus()
    {
        return false;
    }

    /**
     * Prepare the POST query string.
     *
     * You will need PHP_Compat::str_split() if you run this processor
     * under PHP 4.
     *
     * @access private
     * @return string The query string
     */
    function _prepareQueryString()
    {
        $data = array_merge($this->_options, $this->_data);

        // Set payment method to eCheck if our payment type is eCheck.
        // Default is Credit Card.
        $data['x_method'] = 'CC';


        if ($this->_payment instanceof Payment_Process2_Type_eCheck) {
            $data['x_method'] = 'ECHECK';
            switch ($this->_payment->type) {
                case PAYMENT_PROCESS2_CK_CHECKING:
                    $data['x_bank_acct_type'] = 'CHECKING';
                    break;
                case PAYMENT_PROCESS2_CK_SAVINGS:
                    $data['x_bank_acct_type'] = 'SAVINGS';
                    break;
            }
        }

        // Keep a trace of characters we will get back
        // so we can set an appropriate encapsulation character
        $chars = '';

        $return = array();
        foreach ($data as $key => $val) {
            if (substr($key, 0, 2) == 'x_'
                  && $key != 'x_encap_char'
                  && strlen($val)) {
                $return[] = $key . '=' . rawurlencode($val);
                $chars .= $val;
            }
        }

        // Find an appropriate encapsulation character
        $encap = str_replace(array_unique(str_split($chars)), '', $data['x_encap_char']{0} . $this->_encapChars);

        if (strlen($encap) == 0) {
            $encap = $data['x_encap_char']{0};
        } else {
            $encap = $encap{0};
        }
        $this->_options['x_encap_char'] = $encap;
        $return[] = 'x_encap_char=' . rawurlencode($encap);

        return implode('&', $return);
    }

    public function prepareRequestData() {
        $string = $this->_prepareQueryString();

        $data = array();
        parse_str($string, $data);
        return $data;
    }

    /**
     * _handleName
     *
     * If it's an eCheck we need to combine firstName and lastName into a
     * single account name.
     *
     * @author Joe Stump <joe@joestump.net>
     * @access private
     */
    function _handleName()
    {
        if ($this->_payment instanceof Payment_Process2_Type) {
            if ($this->_payment->getType() == 'eCheck') {
                 $this->_data['x_bank_acct_name'] = $this->_payment->firstName.' '.$this->_payment->lastName;
            }
        }
    }

    /**
     * Translates the actions to a localised version
     */
    function translateAction($action) {
        switch ($action) {
            case PAYMENT_PROCESS2_ACTION_NORMAL:
                return 'AUTH_CAPTURE';
                break;

            case PAYMENT_PROCESS2_ACTION_AUTHONLY:
                return 'AUTH_ONLY';
                break;

            case PAYMENT_PROCESS2_ACTION_POSTAUTH:
                return 'PRIOR_AUTH_CAPTURE';
                break;

            case PAYMENT_PROCESS2_ACTION_VOID:
                return 'VOID';
                break;
        }

        return false;
    }
}


?>
