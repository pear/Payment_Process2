<?php
require_once 'PHPUnit/Framework/TestCase.php';

require_once 'HTTP/Request2/Adapter/Mock.php';
require_once 'Payment/Process2.php';

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
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process2
 * @link      http://www.anz.com/
 * @link      http://www.valex.com.au/
 */
class Payment_Process2_ANZTest extends PHPUnit_Framework_TestCase {

    public static function errorProvider() {
        return self::dataProviderHelper('error');
    }

    public static function successProvider() {
        return self::dataProviderHelper('success');
    }




    public static function sandboxDataProviderHelper() {
        if (!defined('ANZ_GATEWAY_LOGIN') || !defined('ANZ_GATEWAY_PASSWORD')) {
            return null;
        }

        $process = self::aProcessor();
        $process->login = ANZ_GATEWAY_LOGIN;
        $process->password = ANZ_GATEWAY_PASSWORD;

        if (OS_WINDOWS) {
            // stupid root certificates on Windows!
            $request = new HTTP_Request2();
            $request->setConfig('ssl_verify_peer', false);
        } else {
            $request = new HTTP_Request2();
            $request->setConfig('ssl_cafile', '/etc/ssl/certs/ca-certificates.crt');
        }
        $process->setRequest($request);
        return array($process);
    }

    public static function mockDataProviderHelper($file) {
        $process = self::aProcessor();

        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse(self::aResponse($file));

        $request = new HTTP_Request2();
        $request->setAdapter($mock);
        $process->setRequest($request);

        return array($process);
    }

    public static function dataProviderHelper($file) {
        $data = array();

        if ($mock = self::mockDataProviderHelper($file)) {
            $data[] = $mock;
        }

        if ($sandbox = self::sandboxDataProviderHelper()) {
            $data[] = $sandbox;
        }

        return $data;
    }

    public static function aProcessor() {
        $process = Payment_Process2::factory('ANZ');
        $process->action = Payment_Process2::ACTION_NORMAL;
        return $process;
    }

    public static function aResponse($file) {
        $fp = fopen(dirname(__FILE__) .'/data/ANZ/'. $file .'.html', 'r');
        return HTTP_Request2_Adapter_Mock::createResponseFromFile($fp);
    }










    /**
     * @dataProvider successProvider
     * @param Payment_Process2_ANZ $process
     */
    public function testShouldProcessPaymentSuccessfully(Payment_Process2_ANZ $process) {

        $cc = $this->aMockANZCard();

        $process->invoiceNumber = '123';
        $process->transactionReference = '123/2';
        $process->amount = 1.00;

        $process->setPayment($cc);

        $result = $process->process();

        $this->assertTrue($result instanceOf Payment_Process2_Result_ANZ);
        $this->assertSame(Payment_Process2::RESULT_APPROVED, $result->getCode(), $result->getMessage());
    }

    /**
     * @dataProvider errorProvider
     * @param Payment_Process2_ANZ $process
     */
    public function testShouldReturnErrorOnPaymentFailure(Payment_Process2_ANZ $process) {

        $cc = $this->aMockANZCard();

        $process->invoiceNumber = '123';
        $process->transactionReference = '123/4';
        $process->amount = 1.33;

        $process->setPayment($cc);

        $result = $process->process();

        $this->assertTrue($result instanceOf Payment_Process2_Result_ANZ);
        $this->assertSame(Payment_Process2::RESULT_DECLINED, $result->getCode(), $result->getMessage());
    }

    public function testShouldMapCardExpiryDataCorrectly() {
    	$processor = self::aProcessor();

    	$cc = $this->aMockANZCard();
    	$cc->expDate = '12/2013';
    	$processor->setPayment($cc);

		$changed_data = $processor->prepareRequestData();
		$this->assertSame('1312', $changed_data["vpc_CardExp"], "Didn't correctly map into YYMM format");
    }

    public function testShouldAlwaysRequireATransactionReference() {

        $payment = Payment_Process2::factory('ANZ');
        $payment->action = Payment_Process2::ACTION_NORMAL;
        $payment->amount = 123.00;
        $payment->setPayment($this->aMockANZCard());

        try {
            $payment->validate();

            $this->fail("Expected an exception because we haven't got anything for vpc_MerchTxnRef");
        } catch (Payment_Process2_Exception $ppe) {
            $this->assertSame("Missing transaction reference", $ppe->getMessage());
        }

    }

    /**
     * Test not sending the security code
     *
     * @dataProvider successProvider
     * @param Payment_Process2_ANZ $process
     */
    public function testShouldAllowTheSecurityCodeToBeSetOptionally(Payment_Process2_ANZ $process) {

        $cc = $this->aMockANZCard();

        $cc->cvv = null;

        $process->invoiceNumber = '123';
        $process->transactionReference = '123/2';
        $process->amount = 1.00;
        $process->setPayment($cc);

        $result = $process->process();

        $this->assertTrue($result instanceOf Payment_Process2_Result_ANZ);
        $this->assertSame(Payment_Process2::RESULT_APPROVED, $result->getCode(), $result->getMessage());
    }

    /**
     * @dataProvider successProvider
     * @param Payment_Process2_ANZ $process
     */
    public function testShouldNotAllowNegativeCharges(Payment_Process2_ANZ $process) {

        $cc = $this->aMockANZCard();

        $process->invoiceNumber = '123';
        $process->transactionReference = '123/2';
        $process->amount = -1.00;

        $process->setPayment($cc);

        try {
            $process->process();
        } catch (Payment_Process2_Exception $e) {
            $this->assertSame('Invalid amount', $e->getMessage());
        }
    }

    /**
     * @deprecated
     *
     * @return Payment_Process2_Type_CreditCard
     */
    public function aMockANZCard() {
        /* @var Payment_Process2_Type_CreditCard $cc */
        $cc = Payment_Process2_Type::factory('MockCreditCard');
        $cc->type = Payment_Process2_Type::CC_MASTERCARD;
        $cc->cardNumber = '5123456789012346';
        $cc->expDate = '13/2005';
        $cc->cvv = '123';
        return $cc;
    }

}
