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

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Payment_Process2_AllTests::main');
}

require_once 'PHPUnit/TextUI/TestRunner.php';



require_once dirname(__FILE__) . '/Payment_Process2Test.php';

require_once dirname(__FILE__) . '/Payment_Process2_ResultTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_Result_AuthorizeNetTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_Result_DummyTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_Result_TrustCommerceTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_Result_LinkPointTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_Result_BibitTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_Result_PayPalTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_Result_TransfirstTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_Result_ANZTest.php';


require_once dirname(__FILE__) . '/Payment_Process2_TypeTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_Type_CreditCardTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_Type_eCheckTest.php';

require_once dirname(__FILE__) . '/Payment_Process2_CommonTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_AuthorizeNetTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_DummyTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_TrustCommerceTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_LinkPointTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_BibitTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_PayPalTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_TransfirstTest.php';
require_once dirname(__FILE__) . '/Payment_Process2_ANZTest.php';

class Payment_Process2_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Payment_Process2 package');

        $suite->addTestSuite('Payment_Process2Test');


        $suite->addTestSuite('Payment_Process2_TypeTest');
        $suite->addTestSuite('Payment_Process2_Type_eCheckTest');
        $suite->addTestSuite('Payment_Process2_Type_CreditCardTest');

        $suite->addTestSuite('Payment_Process2_CommonTest');
        $suite->addTestSuite('Payment_Process2_AuthorizeNetTest');
        $suite->addTestSuite('Payment_Process2_DummyTest');
        $suite->addTestSuite('Payment_Process2_TrustCommerceTest');
        $suite->addTestSuite('Payment_Process2_LinkPointTest');
        $suite->addTestSuite('Payment_Process2_BibitTest');
        $suite->addTestSuite('Payment_Process2_PayPalTest');
        $suite->addTestSuite('Payment_Process2_TransfirstTest');
        $suite->addTestSuite('Payment_Process2_ANZTest');

        $suite->addTestSuite('Payment_Process2_ResultTest');
        $suite->addTestSuite('Payment_Process2_Result_AuthorizeNetTest');
        $suite->addTestSuite('Payment_Process2_Result_DummyTest');
        $suite->addTestSuite('Payment_Process2_Result_TrustCommerceTest');
        $suite->addTestSuite('Payment_Process2_Result_LinkPointTest');
        $suite->addTestSuite('Payment_Process2_Result_BibitTest');
        $suite->addTestSuite('Payment_Process2_Result_PayPalTest');
        $suite->addTestSuite('Payment_Process2_Result_TransfirstTest');
        $suite->addTestSuite('Payment_Process2_Result_ANZTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Payment_Process2_AllTests::main') {
    Payment_Process2_AllTests::main();
}
?>
