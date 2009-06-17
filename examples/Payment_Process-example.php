<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Basic example
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
 * @package   Payment_Process2
 * @author    Ian Eure <ieure@php.net>
 * @copyright 2003-2008 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process2
 */

require 'Payment/Process2.php';

// Set options. These are processor-specific.
$options = array(
    'randomResult' => true
);

// Get an instance of the processor
$processor = Payment_Process::factory('Dummy', $options);

// The data for our transaction.
$data = array(
    'login' => "foo",
    'password' => "bar",
    'action' => Payment_Process2::ACTION_NORMAL,
    'amount' => 15.00
);

// The credit card information
$cc = Payment_Process2_Type::factory('CreditCard');
$cc->type = Payment_Process2_Type::CC_VISA;
$cc->cardNumber = "4111111111111111";
$cc->expDate = "99/99";
$cc->cvv = "123";

/* Alternately, you can use setFrom()
$ccData = array(
    'type' => Payment_Process2_Type::CC_VISA,
    'cardNumber' => "4111111111111111",
    'expDate' => "99/99",
    'cvv' => 123
);
$cc->setFrom($ccData);
*/

// Process it
$processor->setFrom($data);
if (!$processor->setPayment($cc)) {
    throw new Payment_Process2_Exception("Payment data is invalid.");
}
$result = $processor->process();

if ($result->isSuccess()) {
    // Transaction approved
    print "Success: ";
} else {
    // Transaction declined
    print "Failure: ";
}
print $result->getMessage()."\n";

?>