<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Bibit processor
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
 * @package   Payment_Process2
 * @author    Robin Ericsson <lobbin@localhost.nu>
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process2
 */

require_once 'Payment/Process2.php';
require_once 'Payment/Process2/Common.php';
require_once 'Payment/Process2/Driver.php';
require_once 'Payment/Process2/Result/Bibit.php';
require_once 'Net/Curl.php';
require_once 'XML/Util.php';



/**
 * Payment_Process2_Bibit
 *
 * This is a process for Bibit's merchant payment gateway.
 * (http://www.bibit.com)
 *
 * *** WARNING ***
 * This is BETA code, and hos not been fully tested. It is not recommended
 * that you use it in a production environment without further testing.
 *
 * @category Payment
 * @package  Payment_Process2
 * @author   Robin Ericsson <lobbin@localhost.nu>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Payment_Process2
 */
class Payment_Process2_Bibit extends Payment_Process2_Common implements Payment_Process2_Driver
{


    const ACTION_BIBIT_AUTH = 300;
    const ACTION_BIBIT_REDIRECT = 400;
    const ACTION_BIBIT_REFUND =  500;
    const ACTION_BIBIT_CAPTURE = 600;

    /**
     * Front-end -> back-end field map.
     *
     * This array contains the mapping from front-end fields (defined in
     * the Payment_Process class) to the field names Bibit requires.
     *
     * @see _prepare()
     * @access private
     */
    var $_fieldMap = array(
        // Required
        'login' => 'x_login',
        'password' => 'x_password',
        'ordercode' => 'x_ordercode',
        'description' => 'x_descr',
        'amount' => 'x_amount',
        'currency' => 'x_currency',
        'exponent' => 'x_exponent',
        'action' => 'x_action',
        // Optional
        'ordercontent' => 'x_ordercontent',
        'shopper_ip_address' => 'shopperIPAddress',
        'shopper_email_address' => 'shopperEmailAddress',
        'session_id' => 'sessionId',
        'authenticated_shopper_id' => 'authenticatedShopperID',
        'shipping_address' => 'shippingAddress',
        'payment_method_mask' => 'paymentMethodMask',
    );

    /**
     * Default options for this processor.
     *
     * @see Payment_Process::setOptions()
     * @access private
     */
    var $_defaultOptions = array(
        'authorizeUri' => 'https://secure.bibit.com/jsp/merchant/xml/paymentService.jsp',
        'authorizeTestUri' => 'https://secure-test.bibit.com/jsp/merchant/xml/paymentService.jsp',
        'x_version' => '1.4'
    );

    /**
     * The orders unique code
     *
     * @access private
     */
    var $ordercode = '';

    /**
     * The order amounts currency
     *
     * @access private
     */
    var $currency = '';

    /**
     * The order amounts exponent
     *
     * @access private
     */
    var $exponent = 0;

    /**
     * The orders content as displayed at bibit
     *
     * @access private
     */
    var $ordercontent = '';

    /**
     * The ip-address the order comes from
     *
     * @access private
     */
    var $shopper_ip_address;

    /**
     * The shoppers email-address
     *
     * @access private
     */
    var $shopper_email_address;

    /**
     * The unique id of the users session
     *
     * @access private
     */
    var $session_id;

    /**
     * Unique id of the authenticed shopper
     *
     * @access private
     */
    var $authenticated_shopper_id;

    /**
     * Shipping address
     *
     * @access private
     */
    var $shipping_address = array();

    /**
     * Payment method mask
     *
     * @access private
     */
    var $payment_method_mask = array();

    /**
     * $_typeFieldMap
     *
     * @access protected
     */
    var $_typeFieldMap = array(
        'CreditCard' => array(
            'cvv' => 'x_card_code',
            'expDate' => 'x_exp_date',
            'cardNumber' => 'x_card_num',
        )
    );

    /**
     * Constructor.
     *
     * @param array $options Class options to set.
     *
     * @see Payment_Process::setOptions()
     */
    function __construct($options = array(), HTTP_Request2 $request = null)
    {
        parent::__construct($options, $request);
        $this->_driver = 'Bibit';
        $this->_makeRequired('login', 'password', 'ordercode', 'description', 'amount', 'currency', 'exponent', 'cardNumber', 'expDate', 'action');

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

        $url = isset($this->_options['live']) ? $this->_options['authorizeUri'] : $this->_options['authorizeTestUri'];


        $request = clone $this->_request;

        $request->setURL($url);
        $request->setBody($this->renderRequestDocument());
        $request->setMethod('put');
        $request->setAuth($this->_data['x_login'], $this->_data['x_password']);

        $result = $request->send();

        $responseBody = trim($result->getBody());
        $this->_processed = true;


        $response = Payment_Process2_Result::factory($this->_driver,
                                                     $responseBody,
                                                     $this);
        $response->parse();


        return $response;
    }

    public function prepareRequestData() {
        return array();
    }

    /**
     * Prepare the PUT query xml.
     *
     * @access private
     * @return string The query xml
     */
    function renderRequestDocument()
    {
        $data = array_merge($this->_options, $this->_data);


        $doc  = XML_Util::getXMLDeclaration();
        $doc .= '<!DOCTYPE paymentService PUBLIC "-//Bibit//DTD Bibit PaymentService v1//EN" "http://dtd.bibit.com/paymentService_v1.dtd">';

        $doc .= XML_Util::createStartElement('paymentService', array('version' =>  $data['x_version'], 'merchantCode' => $data['x_login']));
        if ($data['x_action'] == Payment_Process2_Bibit::ACTION_BIBIT_CAPTURE || $data['x_action'] == Payment_Process2_Bibit::ACTION_BIBIT_REFUND) {
            $doc .= XML_Util::createStartElement('modify');
            $doc .= XML_Util::createStartElement('orderModification', array('orderCode' => $data['x_ordercode']));
            if ($data['x_action'] == Payment_Process2_Bibit::ACTION_BIBIT_CAPTURE) {
                $doc .= XML_Util::createStartElement('capture');

                $d = array();
                $t = time() - 86400;

                $d['dayOfMonth'] = date('d', $t);
                $d['month']      = date('m', $t);
                $d['year']       = date('Y', $t);
                $d['hour']       = date('H', $t);
                $d['minute']     = date('i', $t);
                $d['second']     = date('s', $t);

                $doc .= XML_Util::createTag('date', $d);
                $doc .= XML_Util::createTag('amount',
                    array('value' => $data['x_amount'],
                          'currencyCode' => $data['x_currency'],
                          'exponent' => $data['x_exponent']));

                $doc .= XML_Util::createEndElement('capture');
            } else if ($data['x_action'] == Payment_Process2_Bibit::ACTION_BIBIT_REFUND) {
                $doc .= XML_Util::createStartElement('refund');
                $doc .= XML_Util::createTag('amount',
                    array('value' => $data['x_amount'],
                          'currencyCode' => $data['x_currency'],
                          'exponent' => $data['x_exponent']));
                $doc .= XML_Util::createEndElement('refund');
            }

            $doc .= XML_Util::createEndElement('orderModification');
            $doc .= XML_Util::createEndElement('modify');
        } else {
            $doc .= XML_Util::createStartElement('submit');
            $doc .= XML_Util::createStartElement('order',
                array('orderCode' => $data['x_ordercode']));

            $doc .= XML_Util::createTag('description', null, $data['x_descr']);
            $doc .= XML_Util::createTag('amount',
                array('value' => $data['x_amount'],
                      'currencyCode' => $data['x_currency'],
                      'exponent' => $data['x_exponent']));
            if (isset($data['x_ordercontent'])) {
                $doc .= XML_Util::createStartElement('orderContent');
                $doc .= XML_Util::createCDataSection($data['x_ordercontent']);
                $doc .= XML_Util::createEndElement('orderContent');
            }

            if ($data['x_action'] == Payment_Process2_Bibit::ACTION_BIBIT_REDIRECT) {
                if (is_array($data['paymentMethodMask'])
                    && count($data['paymentMethodMask'] > 0)) {
                    $doc .= XML_Util::createStartElement('paymentMethodMask');

                    /** @todo Unit test coverage of this ? */
                    if (!empty($data['paymentMethodMask']['include'])) {
                        foreach ($data['paymentMethodMask']['include'] as $code) {
                            $doc .= XML_Util::createTag('include',
                                                        array('code' => $code));
                        }
                    }

                    /** @todo Unit test coverage of this ? */
                    if (!empty($data['paymentMethodMask']['exclude'])) {
                        foreach ($data['paymentMethodMask']['exclude'] as $code) {
                            $doc .= XML_Util::createTag('exclude',
                                                        array('code' => $code));
                        }
                    }

                    $doc .= XML_Util::createEndElement('paymentMethodMask');
                }
            } else if ($data['x_action'] == Payment_Process2_Bibit::ACTION_BIBIT_AUTH) {
                $doc .= XML_Util::createStartElement('paymentDetails');
                switch ($this->_payment->type) {
                case Payment_Process2_Type::CC_VISA:
                    $cc_type = 'VISA-SSL';
                    break;
                case Payment_Process2_Type::CC_MASTERCARD:
                    $cc_type = 'ECMC-SSL';
                    break;
                case Payment_Process2_Type::CC_AMEX:
                    $cc_type = 'AMEX-SSL';
                    break;
                }

                $doc .= XML_Util::createStartElement($cc_type);
                if (isset($data['x_card_num'])) {
                    $doc .= XML_Util::createTag('cardNumber', null,
                                                $data['x_card_num']);
                }
                if (isset($data['x_exp_date'])) {
                    $doc .= XML_Util::createStartElement('expiryDate');
                    $doc .= XML_Util::createTag('date',
                        array('month' => substr($data['x_exp_date'], 0, 2),
                              'year' => substr($data['x_exp_date'], 3, 4)));
                    $doc .= XML_Util::createEndElement('expiryDate');
                }
                if (isset($this->_payment->firstName) &&
                    isset($this->_payment->lastName)) {
                    $doc .= XML_Util::createTag('cardHolderName', null,
                        $this->_payment->firstName.' '.$this->_payment->lastName);
                }
                if (isset($data['x_card_code'])) {
                    $doc .= XML_Util::createTag('cvc', null, $data['x_card_code']);
                }

                $doc .= XML_Util::createEndElement($cc_type);

                if ((isset($data['shopperIPAddress']) || isset($data['sessionId']))
                &&  ($data['shopperIPAddress'] != ''  || $data['sessionId'] != '')) {
                    $t = array();
                    if ($data['shopperIPAddress'] != '') {
                        $t['shopperIPAddress'] = $data['shopperIPAddress'];
                    }
                    if ($data['sessionId'] != '') {
                        $t['id'] = $data['sessionId'];
                    }

                    $doc .= XML_Util::createTag('session', $t);
                    unset($t);
                }

                $doc .= XML_Util::createEndElement('paymentDetails');
            }

            if ((isset($data['shopperEmailAddress'])    && $data['shopperEmailAddress'] != '')
            ||  (isset($data['authenticatedShopperID']) && $data['authenticatedShopperID'] != '')) {
                $doc .= XML_Util::createStartElement('shopper');

                if ($data['shopperEmailAddress'] != '') {
                    $doc .= XML_Util::createTag('shopperEmailAddress', null, $data['shopperEmailAddress']);
                }
                if ($data['authenticatedShopperID'] != '') {
                    $doc .= XML_Util::createTag('authenticatedShopperID', null, $data['authenticatedShopperID']);
                }

                $doc .= XML_Util::createEndElement('shopper');
            }

            if (is_array($data['shippingAddress']) && count($data['shippingAddress']) > 0) {
                $a =& $data['shippingAddress'];

                $doc .= XML_Util::createStartElement('shippingAddress');
                $doc .= XML_Util::createStartElement('address');

                $fields = array('firstName',    'lastName',     'street',
                                'houseName',    'houseNumber',  'houseNumberExtension',
                                'postalCode',   'city',         'state',
                                'countryCode',  'telephoneNumber');

                foreach ($fields as $field) {
                    if (isset($a[$field])) {
                        $doc .= XML_Util::createTag($field, null, $a[$field]);
                    }
                }

                $doc .= XML_Util::createEndElement('address');
                $doc .= XML_Util::createEndElement('shippingAddress');
            }

            $doc .= XML_Util::createEndElement('order');
            $doc .= XML_Util::createEndElement('submit');
        }
        $doc .= XML_Util::createEndElement('paymentService');

        return $doc;
    }

    /**
     * Prepare the ordercontent
     *
     * Docs says max size is 10k
     *
     * @return void
     * @access private
     */
    function _handleOrdercontent()
    {
        $specific = $this->_fieldMap['ordercontent'];
        if (!empty($this->ordercontent)) {
            $this->_data[$specific] = substr($this->ordercontent, 0, 10240);
        }
    }

    /**
     * Validate the merchant account login.
     *
     * @access private
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validateLogin()
    {
        $result = Validate::string($this->login, array(
            'format' => VALIDATE_ALPHA_UPPER,
            'min_length' => 1
        ));

        if (!$result) {
            throw new Payment_Process2_Exception("Invalid login");
        }

        return true;
    }

    /**
     * Validate the merchant account password.
     *
     * @access private
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validatePassword()
    {
        $result = Validate::string($this->password, array(
            'min_length' => 1
        ));

        if (!$result) {
            throw new Payment_Process2_Exception("Invalid login");
        }

        return true;
    }

    /**
     * Validates the ordercode
     *
     * Docs says up to 64 characters, no spaces or specials characters allowed
     *
     * @access private
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validateOrdercode()
    {
        $result = Validate::string($this->ordercode, array(
            'min_length' => 1,
            'max_length' => 64
        ));

        if (!$result) {
            throw new Payment_Process2_Exception("Invalid order code");
        }

        return true;
    }

    /**
     * Validate the order description.
     *
     * Docs says maximum length is 50 characters...
     *
     * @access private
     * @return boolean true if valid, false otherwise
     */
    function _validateDescription()
    {
        $result = Validate::string($this->description, array(
            'min_length' => 1,
            'max_length' => 50,
        ));

        if (!$result) {
            throw new Payment_Process2_Exception("Invalid description");
        }

        return true;
    }

    /**
     * Validate the order amount.
     *
     * Should contain no digits, as those are set with the exponent option.
     *
     * @access private
     * @return boolean true if valid, false otherwise
     */
    function _validateAmount()
    {
        $result = Validate::number($this->amount, array(
            'decimal' => false
        ));

        if (!$result) {
            throw new Payment_Process2_Exception("Invalid amount");
        }

        return true;
    }

    /** Validate the order amount currency
     *
     * The abbrivation for a currency, usually 2-3 chars
     *
     * @access private
     * @return boolean true if valid, false otherwise
     */
    function _validateCurrency()
    {
        $result = Validate::string($this->currency, array(
            'format' => VALIDATE_ALPHA_UPPER,
            'min_length' => 2,
            'max_length' => 3
        ));

        if (!$result) {
            throw new Payment_Process2_Exception("Invalid currency");
        }

        return true;
    }

    /** Validate the exponent of the order amount
     *
     * Occording to the dtd, valid is 0, 2 or 3
     *
     * @access private
     * @return boolean true if valid, false otherwise
     */
    function _validateExponent()
    {
        switch ($this->exponent) {
        case 0:
        case 2:
        case 3:
            return true;
        default:
            return false;
        }
    }

    public function translateAction($action) {
        switch ($action) {
            case Payment_Process2::ACTION_NORMAL:
                return Payment_Process2_Bibit::ACTION_BIBIT_REDIRECT;
            case Payment_Process2::ACTION_AUTHONLY:
                return Payment_Process2_Bibit::ACTION_BIBIT_AUTH;
            case Payment_Process2::ACTION_CREDIT:
                return Payment_Process2_Bibit::ACTION_BIBIT_REFUND;
            case Payment_Process2::ACTION_SETTLE:
                return Payment_Process2_Bibit::ACTION_BIBIT_CAPTURE;
        }

        return false;
    }

    public function getStatus() {
        return false;
    }
}

?>
