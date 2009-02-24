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

class Payment_Process2_ResultTest extends PHPUnit_Framework_TestCase {


    public function testShouldGetAngryWithUnimplementedParse() {
        $object = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $result = $object->parse();
        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldGetAngryWithUnimplementedParseCallback() {
        $object = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $result = $object->parseCallback();
        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldGetAngryWithUnimplementedisLegitimate() {
        $object = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $result = $object->isLegitimate();
        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldCreateObjectWithFactory() {
        $object = Payment_Process2_Result::factory('Dummy', null, new Payment_Process2_Common());

        $this->assertTrue($object instanceOf Payment_Process2_Result_Dummy);
    }

    public function testShouldRaiseErrorWithUnknownTypes() {
        $object = Payment_Process2_Result::factory('Duck', null, new Payment_Process2_Common());

        $this->assertTrue($object instanceOf PEAR_Error);
    }



    public function testShouldDefaultToDeclinedWithUnknownTypes() {
        $result = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $this->assertSame(PAYMENT_PROCESS2_RESULT_DECLINED, $result->getCode());
    }

    public function testShouldRespectInternalSettingsWhenReadingCodes() {
        $result = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $result->_statusCodeMap[null] = PAYMENT_PROCESS2_RESULT_APPROVED;

        $this->assertSame(PAYMENT_PROCESS2_RESULT_APPROVED, $result->getCode());
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
        $r = new Payment_Process2_Result(null, new Payment_Process2_Common());

        $result = $r->validate();
        $this->assertTrue($r instanceof PEAR_Error);
    }

    public function testShouldValidateCorrectly2() {
        $r = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $r->_statusCodeMap[null] = PAYMENT_PROCESS2_RESULT_APPROVED;


        $result = $r->validate();

        $this->assertTrue($result);
    }


    public function testShouldValidateCorrectlyWithAvsCheck1() {
        $processor = Payment_Process2::factory('Dummy');
        $processor->setOption('avsCheck', true);

        $r = new Payment_Process2_Result(null, $processor);
        $r->_statusCodeMap[null] = PAYMENT_PROCESS2_RESULT_APPROVED;

        $result = $r->validate();

        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldValidateCorrectlyWithAvsCheck2() {
        $this->markTestIncomplete("Make this pass the Avs check");

        $processor = Payment_Process2::factory('Dummy');
        $processor->setOption('avsCheck', true);

        $r = new Payment_Process2_Result(null, $processor);
        $r->_statusCodeMap[null] = PAYMENT_PROCESS2_RESULT_APPROVED;

        $result = $r->validate();
        $this->assertTrue($result);
    }


    public function testShouldValidateCorrectlyWithCcvCheck1() {

        $processor = Payment_Process2::factory('Dummy');
        $processor->setOption('ccvCheck', true);

        $r = new Payment_Process2_Result(null, $processor);
        $r->_statusCodeMap[null] = PAYMENT_PROCESS2_RESULT_APPROVED;

        $result = $r->validate();

        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldValidateCorrectlyWithCcvCheck2() {
        $this->markTestIncomplete("Make this pass the Ccv check");

        $processor = Payment_Process2::factory('Dummy');
        $processor->setOption('ccvCheck', true);

        $r = new Payment_Process2_Result(null, $processor);
        $r->_statusCodeMap[null] = PAYMENT_PROCESS2_RESULT_APPROVED;

        $result = $r->validate();

        $this->assertTrue($result);
    }
}