<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * eCheck payment type
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
 * @author    Joe Stump <joe@joestump.net>
 * @author    Ian Eure <ieure@php.net>
 * @copyright 1997-2008 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process2
 */

require_once 'Payment/Process2/Type.php';
require_once 'Payment/Process2/Exception.php';

/**
 * Payment_Process2_Type_eCheck
 *
 * @category Payment
 * @package  Payment_Process2
 * @author   Joe Stump <joe@joestump.net>
 * @author   Ian Eure <ieure@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Payment_Process2
 */
class Payment_Process2_Type_eCheck extends Payment_Process2_Type
{
    /**
     * $_type
     *
     * @var string $_type
     */
    var $_type = 'eCheck';

    /**
     * $type
     *
     * @var $type
     */
    var $type;
    var $accountNumber;
    var $routingCode;
    var $bankName;
    var $driversLicense;
    var $driversLicenseState;

    /**
     * Validates an account number
     *
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validateAccountNumber()
    {
        if (empty($this->accountNumber)) {
            throw new Payment_Process2_Exception('Account number is required');
        }

        return true;
    }

    /**
     * Validates a routing number
     *
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validateRoutingCode()
    {
        if (empty($this->routingCode)) {
            throw new Payment_Process2_Exception('Routing code is required');
        }

        return true;
    }

    /**
     * Validates a bank name
     *
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validateBankName()
    {
        if (empty($this->bankName)) {
            throw new Payment_Process2_Exception('Bank name is required');
        }

        return true;
    }
}

?>