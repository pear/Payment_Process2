<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * TrustCommerce processor
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
 * @author    Joe Stump <joe@joestump.net>
 * @author    Robert Peake <robert.peake@trustcommerce.com>
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process
 */

require_once 'Payment/Process2.php';
require_once 'Payment/Process2/Common.php';
require_once 'Payment/Process2/Driver.php';
require_once 'Payment/Process2/Result/TrustCommerce.php';
require_once 'HTTP/Request2.php';



/**
 * Payment_Process2_TrustCommerce
 *
 * This is a processor for TrustCommerce's merchant payment gateway.
 * (http://www.trustcommerce.com/)
 *
 * Note:
 * If you don't have the TrustCommerce certificate available
 * you will need to setOption('verify_peer', false);
 *
 * Don't do that in production though! Install the certificate!
 *
 * <pre>
 * $request = new HTTP_Request2();
 * $request->setConfig('ssl_verify_peer', false);
 *
 * $payment = Payment_Process2::factory('TrustCommerce', $request);
 * </pre>
 *
 *
 * @package Payment_Process
 * @author Robert Peake <robert.peake@trustcommerce.com>
 * @version @version@
 */
class Payment_Process2_TrustCommerce extends Payment_Process2_Common implements Payment_Process2_Driver {
    /**
     * Front-end -> back-end field map.
     *
     * This array contains the mapping from front-end fields (defined in
     * the Payment_Process class) to the field names TrustCommerce requires.
     *
     * @see _prepare()
     * @access private
     */
    var $_fieldMap = array(
        // Required
        'login' => 'custid',
        'password' => 'password',
        'action' => 'action',
        'amount' => 'amount',
        //PostAuth
        'transactionId' => 'transid',
        // Optional
        'name' => 'name',
        'address' => 'address1',
        'city' => 'city',
        'state' => 'state',
        'country' => 'country',
        'phone' => 'phone',
        'email' => 'email',
        'zip' => 'zip',
        'currency' => 'currency',
        'expDate' => 'exp'
    );

    /**
    * $_typeFieldMap
    *
    * @author Robert Peake <robert.peake@trustcommerce.com>
    * @access protected
    */
    var $_typeFieldMap = array(

           'CreditCard' => array(

                    'cardNumber' => 'cc',
                    'cvv' => 'cvv',
                    'expDate' => 'exp'

           ),

           'eCheck' => array(

                    'routingCode' => 'routing',
                    'accountNumber' => 'account',
                    'name' => 'name'

           )
    );

    /**
     * Default options for this processor.
     *
     * @see Payment_Process::setOptions()
     * @access private
     */
    var $_defaultOptions = array();

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
        $this->_driver = 'TrustCommerce';
    }

    function _validateExpDate()
    {
        if ($this->_payment instanceof Payment_Process2_Type_CreditCard) {
            return empty($this->_data['expDate']);
        }

        return true;
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

        if (empty($this->_payment)) {
            throw new Payment_Process2_Exception("Payment type not set");
        }

        /** @todo Refactor this method, it does two things at once! */
        $fields = $this->prepareRequestData();


        $request = clone $this->_request;
        $request->setURL('https://vault.trustcommerce.com/trans/');
        $request->setMethod('POST');

        $request->addPostParameter($fields);

        $result = $request->send();


        $responseBody = trim($result->getBody());
        $this->_processed = true;

        $response = Payment_Process2_Result::factory($this->_driver,
                                                     $responseBody,
                                                     $this);


        $response->parse();

        return $response;
    }

    /**
     * Get (completed) transaction status.
     *
     * @return boolean status.
     */
    function getStatus()
    {
        return false;
    }

    function prepareRequestData()
    {

        if (empty($this->_payment)) {
            throw new Payment_Process2_Exception("Payment type not set");
        }

        $data = $this->_data;

        if ($this->_payment instanceof Payment_Process2_Type_CreditCard) {
            /* expiration is expressed as mmyy */
            $fulldate = $data['exp'];
            $month = strtok($fulldate,'/');
            $year = strtok('');
            $exp = $month.substr($year,2,2);
            $data['exp'] = $exp;
            /* end expiration mangle */
        }

        /* amount is expressed in cents with leading zeroes */
        $data['amount'] = $data['amount']*100;
        if (strlen($data['amount']) == 1) {
            $data['amount'] = "00".$data['amount'];
        } else if(strlen($data['amount']) < 3) {
            $data['amount'] = "0".$data['amount'];
        } else if(strlen($data['amount']) > 8) {
            $amount_message = 'Amount: '.$data['amount'].' too large.';
            throw new Payment_Process2_Exception($amount_message);
        }
        /* end amount mangle */

        if ($this->_payment instanceof Payment_Process2_Type_CreditCard &&
            $this->action != Payment_Process2::ACTION_POSTAUTH) {
            $data['media'] = 'cc';
        }

        if ($this->_payment instanceof Payment_Process2_Type_eCheck) {
            $data['media'] = 'ach';
        }

        $return = array();
        $sets = array();
        foreach ($data as $key => $val) {
            if (strlen($val)) {
                $return[$key] = $val;
                $sets[] = $key.'='.urlencode($val);
            }
        }

        $this->_options['authorizeUri'] = 'https://vault.trustcommerce.com/trans/?'.implode('&',$sets);

        return $return;
    }

    public function translateAction($action) {
        switch ($action) {
            case Payment_Process2::ACTION_NORMAL:
                return 'sale';
            case Payment_Process2::ACTION_AUTHONLY:
                return 'preauth';
            case Payment_Process2::ACTION_POSTAUTH:
                return 'postauth';
        }

        return false;
    }
}

?>
