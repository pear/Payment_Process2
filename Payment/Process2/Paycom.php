<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Paycom processor
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
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process
 */

require_once 'Payment/Process2.php';
require_once 'Payment/Process2/Common.php';
require_once 'Payment/Process2/Driver.php';
require_once 'Payment/Process2/Result/Paycom.php';
require_once 'HTTP/Request.php';

class Payment_Process2_Paycom extends Payment_Process2_Common implements Payment_Process2_Driver {

    /**
     * Front-end -> back-end field map.
     *
     * This array contains the mapping from front-end fields (defined in
     * the Payment_Process class) to the field names DPILink requires.
     *
     * @see _prepare()
     * @access private
     */
    var $_fieldMap = array(
        // Required
        'login' => 'co_code',
        'password' => 'pwd',
        'action' => 'transtype',
        'invoiceNumber' => 'approval',
        'customerId' => 'user1',
        'amount' => 'price',
        'name' => 'cardname',
        'zip' => 'zip',
        // Optional
        'address' => 'street',
        'state' => 'state',
        'country' => 'contry',
        'phone' => 'phone',
        'email' => 'email',
        'ip' => 'ipaddr',
    );

    /**
    * $_typeFieldMap
    *
    * @author Joe Stump <joe@joestump.net>
    * @access protected
    */
    var $_typeFieldMap = array(
           'CreditCard' => array(
                    'cardNumber' => 'cardnum',
                    'expDate' => 'cardexp'
           )
    );

    /**
     * Default options for this processor.
     *
     * @see Payment_Process::setOptions()
     * @access private
     */
    var $_defaultOptions = array(
         'authorizeUri' => 'https://wnu.com/secure/trans31.cgi',
         'country' => '840'
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
        $this->_driver = 'Paycom';
    }

    /**
     * Process the transaction.
     *
     * @return mixed Payment_Process2_Result on success, PEAR_Error on failure
     */
    function process()
    {
        // Sanity check
        $result = $this->validate();
        if(PEAR::isError($result)) {
            return PEAR::raiseError('validate(): '.$result->getMessage());
        }

        // Prepare the data
        $result = $this->_prepare();
        if (PEAR::isError($result)) {
            return PEAR::raiseError('_prepare(): '.$result->getMessage());
        }

        // Don't die partway through
        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);

        $fields = $this->_prepareQueryString();
        $request =  new HTTP_Request($this->_options['authorizeUri']);
        $request->setMethod(HTTP_REQUEST_METHOD_POST);
        $request->addHeader('User-Agent','PEAR Payment_Process2_Paycom 0.1');
        foreach ($fields as $var => $val) {
            $request->addPostData($var,$val);
        }

        $result = $request->sendRequest();
        if (PEAR::isError($result)) {
            PEAR::popErrorHandling();
            return PEAR::raiseError('Request: '.$result->getMessage());
        }


        $responseBody = trim($request->getResponseBody());
        $this->_processed = true;

        // Restore error handling
        PEAR::popErrorHandling();

        $response = Payment_Process2_Result::factory($this->_driver,
                                                     $responseBody,
                                                     $this);
        if (!PEAR::isError($response)) {
            $response->parse();
        }

        return $response;
    }

    /**
     * Prepare the POST query string.
     *
     * @access private
     * @return string The query string
     */
    function _prepareQueryString()
    {

        $data = array_merge($this->_options,$this->_data);

        $return = array();
        $sets = array();
        foreach ($data as $key => $val) {
            $return[$key] = $val;
            $sets[] = $key.'='.urlencode($val);
        }

        $this->_options['authorizeUri'] .= '?'.implode('&',$sets);

        return $return;
    }

    // {{{ _handleExpDate()
    /**
    * _handleExpDate
    *
    * @author Joe Stump <joe@joestump.net>
    * @access protected
    */
    function _handleExpDate()
    {
        list($month,$year) = explode($this->_data['cardexp']);
        $this->_data['cardexp'] = $month.substr($year,2,2);
    }
    // }}}


    public function translateAction($action) {
        switch ($action) {
            case PAYMENT_PROCESS2_ACTION_NORMAL:
                return 'approveclose';
            case PAYMENT_PROCESS2_ACTION_AUTHONLY:
                return 'approve';
            case PAYMENT_PROCESS2_ACTION_POSTAUTH:
                return 'close';
            case PAYMENT_PROCESS2_ACTION_CREDIT:
                return 'credit';
        }

        return false;
    }

    public function getStatus() {
        return false;
    }
}

?>
