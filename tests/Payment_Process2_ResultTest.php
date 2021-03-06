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
require_once 'Payment/Process2/Result.php';
require_once 'Payment/Process2/Common.php';

class Payment_Process2_ResultTest extends PHPUnit_Framework_TestCase {

    public function aValidPayment() {
        $cc = Payment_Process2_Type::factory('CreditCard');
        $cc->setDate(strtotime('2008-01-01'));
        $cc->type = Payment_Process2_Type::CC_MASTERCARD;
        $cc->cardNumber = '5123456789012346';
        $cc->expDate = '12/2008';
        $cc->cvv = '123';

        return $cc;
    }

    public function testShouldCreateObjectWithFactory() {
        $object = Payment_Process2_Result::factory('Dummy', null, new Payment_Process2_Common());

        $this->assertTrue($object instanceOf Payment_Process2_Result_Dummy);
    }

    public function testShouldRaiseErrorWithUnknownTypes() {
        try {
            $object = Payment_Process2_Result::factory('Duck', null, new Payment_Process2_Common());

            $this->fail("Expected an exception");
        } catch (Payment_Process2_Exception $ppe) {

        }
    }



    public function testShouldDefaultToDeclinedWithUnknownTypes() {
        $result = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $this->assertSame(Payment_Process2::RESULT_DECLINED, $result->getCode());
    }

    public function testShouldRespectInternalSettingsWhenReadingCodes() {
        $result = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $result->_statusCodeMap[null] = Payment_Process2::RESULT_APPROVED;

        $this->assertSame(Payment_Process2::RESULT_APPROVED, $result->getCode());
    }

    public function testShouldRespectInternalSettingsWhenReadingMessages1() {
        $result = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $result->message = "Hi";

        $this->assertSame("Hi", $result->getMessage());
    }

    public function testShouldRespectInternalSettingsWhenReadingMessages2() {
        $result = new Payment_Process2_Result(null, new Payment_Process2_Common());

        $this->assertSame("No message reported", $result->getMessage());
    }


    public function testShouldValidateCorrectly1() {
        $processor = Payment_Process2::factory('Dummy');
        $processor->setPayment($this->aValidPayment());

        $r = new Payment_Process2_Result(null, $processor);

        try {
            $r->validate();

            $this->fail("Expected an exception");
        } catch (Payment_Process2_Exception $ppe) {

        }
    }

    public function testShouldValidateCorrectly2() {
        $r = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $r->_statusCodeMap[null] = Payment_Process2::RESULT_APPROVED;


        $result = $r->validate();

        $this->assertTrue($result);
    }


    public function testShouldValidateCorrectly3() {
        $r = new Payment_Process2_Result(null, new Payment_Process2_Common());

        try {
            $r->validate();

            $this->fail("Expected an exception");
        } catch (Payment_Process2_Exception $ppe) {

        }
    }


    public function testShouldValidateCorrectlyWithAvsCheck1() {
        $processor = Payment_Process2::factory('Dummy');
        $processor->setOption('avsCheck', true);

        $processor->setPayment($this->aValidPayment());

        $r = new Payment_Process2_Result(null, $processor);
        $r->_statusCodeMap[null] = Payment_Process2::RESULT_APPROVED;

        try {
            $r->validate();

            $this->fail("Expected an exception");
        } catch (Payment_Process2_Exception $ppe) {

        }
    }

    public function testShouldValidateCorrectlyWithAvsCheck2() {
        $this->markTestIncomplete("Make this pass the Avs check");

        $processor = Payment_Process2::factory('Dummy');
        $processor->setOption('avsCheck', true);

        $processor->setPayment($this->aValidPayment());

        $r = new Payment_Process2_Result(null, $processor);
        $r->_statusCodeMap[null] = Payment_Process2::RESULT_APPROVED;

        $result = $r->validate();
        $this->assertTrue($result);
    }

    /** @todo Think if this should be a per result driver test */
    public function testShouldValidateCorrectlyWithCvvCheck1() {

        $processor = Payment_Process2::factory('Dummy');
        $processor->setOption('cvvCheck', true);

        $processor->setPayment($this->aValidPayment());

        $r = new Payment_Process2_Result(null, $processor);
        $r->_statusCodeMap[null] = Payment_Process2::RESULT_APPROVED;

        $this->assertSame(null, $r->getCvvCode(), "Expected this to be null; a generic result shouldn't understand any cvv codes");

        try {
            $r->validate();

            $this->fail("Expected an exception: We haven't got a Cvv code set");
        } catch (Payment_Process2_Exception $ppe) {

        }
    }

    public function testShouldValidateCorrectlyWithCvvCheck2() {
        $processor = Payment_Process2::factory('Dummy');
        $processor->setOption('cvvCheck', true);

        $processor->setPayment($this->aValidPayment());

        $r = new Payment_Process2_Result(null, $processor);
        $r->_statusCodeMap[null] = Payment_Process2::RESULT_APPROVED;

        $r->_cvvCodeMap[1] = Payment_Process2::CVV_MATCH;
        $r->cvvCode = 1;

        $this->assertSame(Payment_Process2::CVV_MATCH, $r->getCvvCode());

        $result = $r->validate();

        $this->assertTrue($result);
    }


    public function testShouldValidateCorrectlyWithCvvCheck3() {
        $processor = Payment_Process2::factory('Dummy');
        $processor->setOption('cvvCheck', true);

        $processor->setPayment($this->aValidPayment());

        $r = new Payment_Process2_Result(null, $processor);
        $r->_statusCodeMap[null] = Payment_Process2::RESULT_APPROVED;

        $r->_cvvCodeMap[1] = Payment_Process2::CVV_MATCH;
        $r->cvvCode = 2;

        $this->assertNotSame(Payment_Process2::CVV_MATCH, $r->getCvvCode());

        try {
            $r->validate();

            $this->fail("Expected an exception: We haven't got a valid Cvv code set");
        } catch (Payment_Process2_Exception $ppe) {

        }
    }
}