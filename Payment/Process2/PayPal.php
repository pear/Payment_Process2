<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PayPal "Direct Payment" processor
 *
 * NOTE: NOT COMPLETED YET.
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
 * @category   Payment
 * @package    Payment_Process2
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2007 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Payment_Process2
 * @todo       Complete the implementation
 */

/**
 * Required classes
 */
require_once 'Payment/Process2.php';
require_once 'Payment/Process2/Common.php';
require_once 'Payment/Process2/Driver.php';
require_once 'Payment/Process2/Result/PayPal.php';
require_once 'HTTP/Request2.php';


/**
 * This is a processor for PayPal's merchant  Direct Payment gateway.
 *
 * @package    Payment_Process2
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @version    @version@
 * @link       http://www.paypal.com/
 */
class Payment_Process2_PayPal extends Payment_Process2_Common implements Payment_Process2_Driver
{
    /**
     * Front-end -> back-end field map.
     *
     * This array contains the mapping from front-end fields (defined in
     * the Payment_Process class) to the field names PayPal requires.
     *
     * @see _prepare()
     * @access private
     */
    var $_fieldMap = array(
        // Required
        'login'         => 'USER',
        'password'      => 'PWD',

        // Optional
        'invoiceNumber' => 'INVNUM',
        'customerId'    => 'CUSTOM',
        'amount'        => 'AMT',
        'description'   => 'DESC',
        'name'          => '',
        'postalCode'    => 'ZIP',
        'zip'           => 'ZIP',
        'company'       => 'STREET2',
        'address'       => 'STREET',
        'city'          => 'CITY',
        'state'         => 'STATE',
        'country'       => 'COUNTRYCODE',
        'phone'         => 'PHONENUM',
        'email'         => 'EMAIL',
        'ip'            => 'IPADDRESS',
    );

    var $_action = "PAYMENTACTION";

    /**
     * $_typeFieldMap
     *
     * @access protected
     */
    var $_typeFieldMap = array(

           'CreditCard' => array(
                'firstName'  => 'FIRSTNAME',
                'lastName'   => 'LASTNAME',
                'cardNumber' => 'ACCT',
                'cvv'        => 'CVV2',
                'type'       => 'CREDITCARDTYPE',
                'expDate'    => 'EXPDATE',
           ),
    );

    /**
     * Default options for this processor.
     *
     * @see Payment_Process::setOptions()
     * @access private
     */
    protected $defaultOptions = array(
        'paypalUri' => 'https://api-3t.sandbox.paypal.com/nvp',
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
        $this->_driver = 'PayPal';
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
        $this->validate();

        // Prepare the data
        $this->_prepare();


        $fields = $this->prepareRequestData();
        $fields['VERSION'] = '3.2';

        $request = clone $this->_request;
        $request->setURL($this->_options['paypalUri']);
        $request->setMethod('post');
        $request->addPostParameter($fields);


        $result = $request->send();


        $responseBody = trim($result->getBody());
        $this->_processed = true;


        $response = Payment_Process2_Result::factory($this->_driver,
                                                     $responseBody,
                                                     $this);

        $response->parse();

        $r = $response->isLegitimate();
        if ($r === false) {
            throw new Payment_Process2_Exception('Illegitimate response from gateway');
        }

        $response->action = $this->action;

        return $response;
    }

    /**
     * Handles action
     *
     * Actions are defined in translateAction and then
     * handled here.
     *
     * @access protected
     */
    function _handleAction()
    {
        switch ($this->action) {
            case Payment_Process2::ACTION_NORMAL:
                $this->_data['PAYMENTACTION'] = 'Sale';
                $this->_data['METHOD'] = 'DoDirectPayment';
                break;
            case Payment_Process2::ACTION_AUTHONLY:
                $this->_data['PAYMENTACTION'] = 'Authorization';
                $this->_data['METHOD'] = 'DoDirectPayment';
                break;
            case Payment_Process2::ACTION_POSTAUTH:
                $this->_data['METHOD'] = 'DoCapture';
                break;
            case Payment_Process2::ACTION_VOID    :
                $this->_data['METHOD'] = 'DoVoid';
                break;
            case Payment_Process2::ACTION_CREDIT  :
                $this->_data['METHOD'] = 'RefundTransaction';
                break;
        }
    }

    /**
     * Processes a callback from payment gateway
     *
     * Success here doesn't mean the transaction was approved. It means
     * the callback was received and processed without technical difficulties.
     *
     * @return Payment_Process2_Result instance on success, PEAR_Error on failure
     * @todo Implement support for PayPal IPN???
     */
    function processCallback()
    {
        $result =& parent::processCallback();
        return $result;
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

    function prepareRequestData()
    {
        return array_merge($this->_options, $this->_data);
    }


    public function translateAction($action) {
        switch ($action) {
            case Payment_Process2::ACTION_NORMAL:
                return 'Sale';
            case Payment_Process2::ACTION_AUTHONLY:
                return 'Authorization';
            case Payment_Process2::ACTION_POSTAUTH:
                return 'DoCapture';
            case Payment_Process2::ACTION_VOID:
                return 'DoVoid';
            case Payment_Process2::ACTION_CREDIT:
                return 'RefundTransaction';
        }

        return false;
    }
}


?>
