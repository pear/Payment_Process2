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
require_once 'Payment/Process2/Common.php';

class Payment_Process2_CommonTest extends PHPUnit_Framework_TestCase {
    public function aValidPayment() {
        $object = Payment_Process2_Type::factory('eCheck');

        $object->accountNumber = 1;
        $object->routingCode = 2;
        $object->bankName = "Unit test";

        return $object;
    }

    public function aValidProcessor() {
        $object = new Payment_Process2_Common();
        $object->amount = 1;
        $object->_typeFieldMap = array('eCheck' => array('bankName' => 'xyz'));

        return $object;
    }


    public function testShouldSetOptionsFromConstructor() {
        $object = new Payment_Process2_Common(array('hello' => 'world'));

        $this->assertSame('world', $object->_options['hello']);
    }


    public function testShouldSetPaymentCorrectlyForValidPayments() {
        $object = $this->aValidProcessor();
        $payment = $this->aValidPayment();

        $result = $object->setPayment($payment);

        $this->assertTrue($result);
    }


    public function testShouldValidatePaymentsWhenSetting() {
        $object = $this->aValidProcessor();

        $payment = $this->aValidPayment();
        unset($payment->bankName);

        $result = $object->setPayment($payment);
        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldRaiseErrorForUnknownMappingsWithPaymentsWhenSetting() {
        $object = $this->aValidProcessor();
        $object->_typeFieldMap = array();

        $payment = $this->aValidPayment();

        $result = $object->setPayment($payment);
        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldEvaluateMappingMethodsWithPaymentsWhenSetting() {
        $this->markTestIncomplete("Cover off calling child methods on the payment / processor as defined by typemapping - '_handleFoo()';");
    }

    public function testShouldPushUnmappablePartsIntoDataArrayWithPaymentsWhenSetting() {
        $this->markTestIncomplete("Cover off calling child methods on the payment / processor as defined by typemapping; but populating _data");
    }

    public function testShouldGetAListOfSettablePublicFields() {
        $object = new Payment_Process2_Common();

        $fields = array('login', 'password', 'action', 'description', 'amount', 'invoiceNumber', 'customerId', 'transactionSource');
        $result = $object->getFields();

        sort($fields);
        sort($result);
        $this->assertSame($fields, $result);
    }

    public function testShouldCheckAListOfSettablePublicFields() {
        $object = new Payment_Process2_Common();

        $this->assertTrue($object->fieldExists('login'));
        $this->assertFalse($object->fieldExists('violins'));
    }

    public function testShouldPopulatePublicFieldsWithData() {
        $object = new Payment_Process2_Common();

        $data = array('login' => 'hello', 'password' => 'world');

        $object->setFrom($data);

        $this->assertSame('hello', $object->login);
        $this->assertSame('world', $object->password);
    }

    public function testShouldGetAngryWithUnimplementedProcess() {
        $object = new Payment_Process2_Common();
        $result = $object->process();
        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldGetAngryWithUnimplementedProcessCallback() {
        $object = new Payment_Process2_Common();
        $result = $object->processCallback();
        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldGetAngryWithUnimplementedGetResult() {
        $object = new Payment_Process2_Common();
        $result = $object->getResult();
        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldValidateAllFields1() {
        $object = $this->aValidProcessor();
        $payment = $this->aValidPayment();

        $object->setPayment($payment);

        $result = $object->validate();

        $this->assertTrue($result);
    }

    public function testShouldValidateAllFields2() {
        $object = $this->aValidProcessor();
        $payment = $this->aValidPayment();
        unset($payment->bankName);

        $object->setPayment($payment);

        $result = $object->validate();

        $this->assertTrue($result instanceOf PEAR_Error);
    }

    public function testShouldSetFieldsCorrectly1() {
        $object = $this->aValidProcessor();

        $this->assertTrue($object->set('login', 'cats'));
        $this->assertSame('cats', $object->login);
    }


    public function testShouldSetFieldsCorrectly2() {
        $object = $this->aValidProcessor();

        $result = $object->set('violins', 'cats');
        $this->assertTrue($result instanceOf PEAR_Error);
        $this->assertSame('', $object->login);
    }

    public function testShouldModelRequiredFieldsCorrectly() {
        $object = $this->aValidProcessor();

        $object->_required['violins'] = true;

        $this->assertFalse($object->isRequired('cats'));
        $this->assertTrue($object->isRequired('violins'));
    }

    public function testShouldSetOptionsCorrectly1() {
        $object = new Payment_Process2_Common();

        $data = array('login' => 'hello', 'password' => 'world');

        $object->setOptions($data);
        $object->setOption('fish', 'heads');

        $this->assertSame('hello', $object->getOption('login'));
        $this->assertSame('world', $object->getOption('password'));
        $this->assertSame('heads', $object->getOption('fish'));
    }

    public function testShouldSetOptionsCorrectly2() {
        $this->markTestIncomplete('Check that the second argument in setOptions() is available, it merges correctly');
    }

    public function testShouldSetOptionsCorrectly3() {
        $object = new Payment_Process2_Common();

        try {
            $object->setOptions(null);

            $this->fail("Should have raised exceptiosn");
        } catch (InvalidArgumentException $iae) {
        }
    }

/*
Untested, protected code:

function _isDefinedConst($value, $class)
function _makeRequired()
function _makeOptional()
function _validateType()
function _validateAction()
function _validateSource()
function _validateAmount()
function _handleAction()
function _prepare()
*/
}