<?php
/**
 * Unit tests for Payment_Process2 package
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008, 2009, Daniel O'Connor
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   HTTP
 * @package    Payment_Process2
 * @author     Daniel O'Connor <daniel.oconnor@valex.com.au>
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Payment_Process2
 */

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Payment/Process2.php';
require_once 'HTTP/Request2.php';
require_once 'HTTP/Request2/Adapter/Mock.php';

class Payment_Process2_TransfirstTest extends PHPUnit_Framework_TestCase {

    public function aValidPayment() {
        $cc = Payment_Process2_Type::factory('CreditCard');
        $cc->setDate(strtotime('2008-01-01'));
        $cc->type = Payment_Process2_Type::CC_MASTERCARD;
        $cc->cardNumber = '5123456789012346';
        $cc->expDate = '12/2008';
        $cc->cvv = '123';

        return $cc;
    }

    public function testShouldLoadClassFromFactory() {
        $payment = new Payment_Process2();
        $object = $payment->factory('Transfirst');

        $this->assertTrue($object instanceOf Payment_Process2_Transfirst);
    }


    public function testShouldCompleteTransaction() {

        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse('HTTP/1.1 200 OK');

        $request = new HTTP_Request2();
        $request->setAdapter($mock);

        $object = Payment_Process2::factory('Transfirst');
        $object->login = '12345678';
        $object->password = 'test56';
        $object->invoiceNumber = 'test5';
        $object->customerId = 'test5test0test5';
        $object->action = Payment_Process2::ACTION_NORMAL;
        $object->amount = 1;

        $object->setRequest($request);
        $object->setPayment($this->aValidPayment());

        $result = $object->process();

        $this->assertTrue($result instanceof Payment_Process2_Result_Transfirst);
    }

    public function testShouldFailGracefullyOnFailedTransaction() {

        $mock = new HTTP_Request2_Adapter_Mock();
        $mock->addResponse('HTTP/1.1 404 Not Found');

        $request = new HTTP_Request2();
        $request->setAdapter($mock);

        $object = Payment_Process2::factory('Transfirst');
        $object->login = '12345678';
        $object->password = 'test56';
        $object->invoiceNumber = 'test5';
        $object->customerId = 'test5test0test5';
        $object->amount = 1;
        $object->action = Payment_Process2::ACTION_NORMAL;

        $object->setRequest($request);
        $object->setPayment($this->aValidPayment());

        $result = $object->process();

        $this->assertTrue($result instanceof PEAR_Error);
    }

    public function testShouldValidateLoginIsAn8DigitNumber() {
        $this->markTestIncomplete("Not yet covered");
    }

    public function testShouldValidatePasswordIsA6To10CharacterAlphaNumericString() {
        $this->markTestIncomplete("Not yet covered");
    }

    public function testShouldValidateInvoiceNumberIsA5CharacterAlphaNumericString() {
        $this->markTestIncomplete("Not yet covered");
    }

    public function testShouldValidateCustomerIdIsA15CharacterAlphaNumericString() {
        $this->markTestIncomplete("Not yet covered");
    }


    /*
function __construct($options = false)
function process()
function processCallback()
function getStatus()
function _prepareQueryString()
function _handleName()
    */
}