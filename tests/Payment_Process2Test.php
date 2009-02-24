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

class Payment_Process2Test extends PHPUnit_Framework_TestCase {

    public function testShouldGetAListOfSettablePublicFields() {
        $object = new Payment_Process2();

        $fields = array();
        $result = $object->getFields();

        $this->assertSame($fields, $result);
    }

    public function testShouldCheckAListOfSettablePublicFields() {
        $object = new Payment_Process2();

        $this->assertFalse($object->fieldExists('violins'));
    }

    public function testShouldModelRequiredFieldsCorrectly() {
        $object = new Payment_Process2();

        $object->_required['violins'] = true;

        $this->assertFalse($object->isRequired('cats'));
        $this->assertTrue($object->isRequired('violins'));
    }

    public function testShouldSetOptionsCorrectly1() {
        $object = new Payment_Process2();

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

    public function testShouldInspectResultObjects1() {
        $object = new Payment_Process2();
        $this->assertFalse($object->isSuccess(new Payment_Process2_Result(null, new Payment_Process2_Common())));


        $result = new Payment_Process2_Result(null, new Payment_Process2_Common());
        $result->_statusCodeMap[null] = PAYMENT_PROCESS2_RESULT_APPROVED;

        $this->assertTrue($object->isSuccess($result));

    }

    public function testShouldCreateObjectWithFactory() {
        $object = Payment_Process2::factory('Dummy');

        $this->assertTrue($object instanceOf Payment_Process2_Dummy);
    }

    public function testShouldRaiseErrorWithUnknownTypes() {
        $object = Payment_Process2::factory('Duck');

        $this->assertTrue($object instanceOf PEAR_Error);
    }
}