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
require_once 'Payment/Process2/Type.php';

class Payment_Process2_TypeTest extends PHPUnit_Framework_TestCase {

    public function testShouldCreateObjectWithFactory() {
        $object = Payment_Process2_Type::factory('CreditCard');

        $this->assertTrue($object instanceOf Payment_Process2_Type_CreditCard);
    }

    public function testShouldCreateObjectWithFactory2() {
        $object = Payment_Process2_Type::factory('eCheck');

        $this->assertTrue($object instanceOf Payment_Process2_Type_ECheck);
    }

    public function testShouldRaiseErrorWithUnknownTypes() {
        try {
            $object = Payment_Process2_Type::factory('Duck');

            $this->fail("Should have raised an exception");
        } catch (Payment_Process2_Exception $ppe) {
        }
    }

    public function testShouldKnowWhenObjectsAreInvalid() {
        $object = Payment_Process2_Type::factory('CreditCard');

        try {
            $object->validate();

            $this->fail("Should have raised an exception");
        } catch (Payment_Process2_Exception $ppe) {
        }
    }

    public function testShouldValidateObjectsCorrectly() {
        $object = Payment_Process2_Type::factory('eCheck');

        $object->accountNumber = 1;
        $object->routingCode = 2;
        $object->bankName = "Unit test";

        $this->assertTrue($object->validate());
    }

    public function testShouldValidateEmailsCorrectly1() {
        $object = Payment_Process2_Type::factory('eCheck');
        $object->email = 'daniel.oconnor at gmail.com';
        $object->accountNumber = 1;
        $object->routingCode = 2;
        $object->bankName = "Unit test";


        try {
            $object->validate();

            $this->fail("Should have raised an exception");
        } catch (Payment_Process2_Exception $ppe) {
        }
    }

    public function testShouldValidateEmailsCorrectly2() {
        $object = Payment_Process2_Type::factory('eCheck');
        $object->email = 'daniel.oconnor@gmail.com';
        $object->accountNumber = 1;
        $object->routingCode = 2;
        $object->bankName = "Unit test";

        $this->assertTrue($object->validate());
    }

    public function testShouldValidateAmericanZipCodesCorrectly1() {
        $object = Payment_Process2_Type::factory('eCheck');
        $object->country = 'us';
        $object->zip = 123456;
        $object->accountNumber = 1;
        $object->routingCode = 2;
        $object->bankName = "Unit test";

        try {
            $object->validate();

            $this->fail("Should have raised an exception");
        } catch (Payment_Process2_Exception $ppe) {
        }
    }

    public function testShouldValidateAmericanZipCodesCorrectly2() {
        $object = Payment_Process2_Type::factory('eCheck');
        $object->country = 'us';
        $object->zip = 12345;
        $object->accountNumber = 1;
        $object->routingCode = 2;
        $object->bankName = "Unit test";

        $this->assertTrue($object->validate());
    }


    public function testShouldValidateAmericanZipCodesCorrectly3() {
        $object = Payment_Process2_Type::factory('eCheck');
        $object->zip = 123456;
        $object->accountNumber = 1;
        $object->routingCode = 2;
        $object->bankName = "Unit test";

        $this->assertTrue($object->validate());
    }
}
