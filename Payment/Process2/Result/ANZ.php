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

require_once 'Payment/Process2/Result.php';


/**
 * Driver for the ANZ Bank's eGate Payment Web Service (Merchant-Hosted)
 *
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
 * @category  Payment
 * @package   Payment_Process2
 * @author    Daniel O'Connor <daniel.oconnor@valex.com.au>
 * @author    Damien Bezborodov <damien.bezborodow@valex.com>
 * @copyright 2009 The PHP Group
 * @copyright 2009 Valuation Exchange Pty Ltd
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link      http://pear.php.net/package/Payment_Process2
 * @link      http://www.anz.com/
 * @link      http://www.valex.com.au/
 */
class Payment_Process2_Result_ANZ extends Payment_Process2_Result
{

    var $_statusCodeMap = array(
        '0' => Payment_Process2::RESULT_APPROVED,
        '1' => Payment_Process2::RESULT_OTHER,
        '2' => Payment_Process2::RESULT_DECLINED,
        '3' => Payment_Process2::RESULT_OTHER,
        '4' => Payment_Process2::RESULT_DECLINED,
        '5' => Payment_Process2::RESULT_DECLINED,
        '6' => Payment_Process2::RESULT_OTHER,
        '7' => Payment_Process2::RESULT_DECLINED,
        '8' => Payment_Process2::RESULT_DECLINED,
        '9' => Payment_Process2::RESULT_DECLINED,
    );

    var $_statusCodeMessages = array(
        '0' => 'Transaction approved',
        '1' => 'Transaction could not be processed',
        '2' => 'Transaction declined - contact issuing bank',
        '3' => 'No reply from Processing Host',
        '4' => 'Card has expired',
        '5' => 'Insufficient credit',
        '6' => 'Error communicating with Bank',
        '7' => 'Message detail error',
        '8' => 'Transaction declined - transaction type not supported',
        '9' => 'Bank declined transaction - do not contact bank',
    );

    var $_avsCodeMap = array(
    );

    var $_avsCodeMessages = array(
    );

    var $_cvvCodeMap = array(
    );

    var $_cvvCodeMessages = array(
    );

    var $_fieldMap = array('vpc_TxnResponseCode' => 'code',
                           'vpc_TransactionNo' => 'transactionId',
                           'vpc_Message' => 'message',
                           'vpc_CSCResultCode' => 'cvvCheck',
                           'vpc_ReceiptNo' => 'receiptNumber'
    );

    var $receiptNumber;

    /**
     * Class constructor
     *
     * @param string $rawResponse Raw response
     * @param mixed  $request     Request
     */
    public function __construct($rawResponse, $request)
    {
        $this->_rawResponse = $rawResponse;
        $this->_request     = $request;
    }

    /**
     * Parse a response and map the appropriate fields
     *
     * @return null
     */
    public function parse()
    {
        parse_str($this->_rawResponse, $responseArray);
        $this->_mapFields($responseArray);
    }
}
