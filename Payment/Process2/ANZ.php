<?php
/**
 * COPYRIGHT NOTICE:
 *
 * This driver is contributed by Valuation Exchange Pty Ltd to The PHP Group
 * with permission from Australia and New Zealand Banking Group Limited. The
 * ANZ Bank in no way endorse or support this driver, which is provided under
 * the below BSD License.
 *
 * This driver is designed to meet specifications provided by MasterCard and
 * the ANZ Bank. Those specifications are available from www.anz.com and are
 * the intellectual property of MasterCard.
 *
 * This copyright notice must be retained, as required by the BSD Lisence.
 *
 * LICENSE:
 *
 * Copyright (c) 2009, Valuation Exchange Pty Ltd
 * Copyright (c) 2009, The PHP Group
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
 * PHP version 5
 *
 * @category  Payment
 * @package   Payment_Process2
 * @author    Daniel O'Connor <daniel.oconnor@valex.com.au>
 * @author    Damien Bezborodov <damien.bezborodow@valex.com.au>
 * @copyright 2009 The PHP Group
 * @copyright 2009 Valuation Exchange Pty Ltd
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process2
 * @link      http://www.anz.com/
 * @link      http://www.valex.com.au/
 */

require_once 'Payment/Process2.php';
require_once 'Payment/Process2/Common.php';
require_once 'Payment/Process2/Driver.php';
require_once 'HTTP/Request2.php';

/**
 * Driver for the ANZ Bank's eGate Payment Web Service (Merchant-Hosted)
 *
 * @category  Payment
 * @package   Payment_Process2
 * @author    Daniel O'Connor <daniel.oconnor@valex.com.au>
 * @author    Damien Bezborodov <damien.bezborodow@valex.com.au>
 * @copyright 2009 The PHP Group
 * @copyright 2009 Valuation Exchange Pty Ltd
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link      http://pear.php.net/package/Payment_Process2
 * @link      http://www.anz.com/
 * @link      http://www.valex.com.au/
 */
class Payment_Process2_ANZ extends Payment_Process2_Common implements Payment_Process2_Driver
{

    var $transactionReference = '';

    /**
     * Front-end -> back-end field map.
     *
     * This array contains the mapping from front-end fields (defined in
     * the Payment_Process class) to the field names TrustCommerce requires.
     *
     * @see _prepare()
     * @access private
     * @todo Swap to protected
     */
    var $_fieldMap = array(
        // Required
        'login' => 'vpc_Merchant',
        'password' => 'vpc_AccessCode',
        'action' => 'vpc_Command',
        'amount' => 'vpc_Amount',
        'invoiceNumber' => 'vpc_OrderInfo',
        'transactionReference' => 'vpc_MerchTxnRef',
    );

    /**
    * $_typeFieldMap
    *
    * @access protected
     * @todo Swap to protected
    */
    var $_typeFieldMap = array(
        'CreditCard' => array(
            'cardNumber' => 'vpc_CardNum',
            'cvv' => 'vpc_CardSecurityCode',
            'expDate' => 'vpc_CardExp',
        ),
    );

    /**
     * @todo Swap to protected
     */
    var $_defaultOptions = array(
        'url' => 'https://migs.mastercard.com.au/vpcdps',
    );

    /**
     * Constructor.
     *
     * @param array         $options Class options to set.
     * @param HTTP_Request2 $request Request object to use (optional)
     *
     * @see Payment_Process::setOptions()
     * @return void
     */
    public function __construct($options = array(), HTTP_Request2 $request = null)
    {
        parent::__construct($options, $request);
        $this->_driver = 'ANZ';

        $this->_makeRequired('login', 'password', 'action', 'amount',
                             'invoiceNumber', 'transactionReference');
    }

    /**
     * Process a payment
     *
     * @return Payment_Process2_Result_ANZ
     */
    public function process()
    {
        // Sanity check
        $this->validate();

        // Prepare the data
        $this->_prepare();

        $request = clone $this->_request;
        $request->setUrl($this->getOption('url'));
        $request->setMethod(HTTP_Request2::METHOD_POST);

        $request->addPostParameter($this->prepareRequestData());

        $response = $request->send();
        if ($response->getStatus() != 200) {

            $error_msg = "Payment Gateway HTTP Error "
                         . $response->getStatus() . " "
                         . $response->getReasonPhrase();

            throw new Payment_Process2_Exception($error_msg);
        }

        $result = Payment_Process2_Result::factory($this->_driver,
                                         $response->getBody(),
                                         $this);

        $result->parse();

        return $result;
    }

    /**
     * Manipulate the data to be used in the request
     *
     * @return array
     */
    public function prepareRequestData()
    {
        $data = array();

        $data['vpc_Version'] = '1';

        foreach ($this->_data as $name => &$value) {
            if (!substr($name, 0, 4) == 'vpc_') {
                continue;
            }
            if ($name == 'vpc_CardExp') {
                list($month, $year) = explode('/', $value);

                $value = substr($year, -2) . $month;
            }
            if ($name == 'vpc_Amount') {
                $value = floor($value*100);
            }
            $data[$name] = $value;
        }

        return $data;
    }

    /**
     * Translate a Payment_Process2 action
     * into a driver specific action
     *
     * @param string $action Ie, Payment_Process2::ACTION_NORMAL
     *
     * @return string|bool
     */
    public function translateAction($action)
    {
        switch ($action) {
        case Payment_Process2::ACTION_NORMAL:
            return 'pay';
        }

        return false;
    }

    /**
     * Get driver status
     *
     * @return bool
     */
    public function getStatus()
    {
        return false;
    }

    /**
     * Validate an amount
     *
     * @throws Payment_Process2_Exception
     * @return bool
     */
    public function _validateTransactionReference()
    {
        if (empty($this->transactionReference)) {
            throw new Payment_Process2_Exception('Missing transaction reference');
        }

        return true;
    }

    /**
     * Validate an amount
     *
     * @throws Payment_Process2_Exception
     * @return bool
     */
    public function _validateAmount()
    {
        if (empty($this->amount) || $this->amount < 0) {
            throw new Payment_Process2_Exception('Invalid amount');
        }

        return true;
    }
}
