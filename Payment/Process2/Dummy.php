<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Dummy processor
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
 * @package   Payment_Process
 * @author    Ian Eure <ieure@php.net>
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process
 */

require_once 'Payment/Process2/Common.php';
require_once 'Payment/Process2/Driver.php';
require_once 'Payment/Process2/Result/Dummy.php';

/**
 * Dummy processor
 *
 * A dummy processor for offline testing. It can be made to return different
 * result codes and messages for testing purposes.
 *
 * @category Payment
 * @package  Payment_Process
 * @author   Ian Eure <ieure@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Payment_Process
 */
class Payment_Process2_Dummy extends Payment_Process2_Common implements Payment_Process2_Driver
{
    /**
     * Default options for this class.
     *
     * @access private
     * @type array
     * @see Payment_Process::setOptions()
     */
    var $_defaultOptions = array(
        'randomResult' => true,
        'returnCode' => PAYMENT_PROCESS2_RESULT_APPROVED,
        'returnMessage' => "Dummy payment approved"
    );

    var $_returnValues = array(
        array(
            'code' => PAYMENT_PROCESS2_RESULT_APPROVED,
            'message' => "Approved"
        ),
        array(
            'code' => PAYMENT_PROCESS2_RESULT_DECLINED,
            'message' => "Declined"
        ),
        array(
            'code' => PAYMENT_PROCESS2_RESULT_OTHER,
            'message' => "System error"
        )
    );

    /**
     * Process the (dummy) transaction
     *
     * @return mixed  Payment_Process2_Result instance or PEAR_Error
     */
    function process()
    {
        // Sanity check
        if (PEAR::isError($res = $this->validate())) {
            return($res);
        }

        if ($this->_options['randomResult']) {
            $n       = rand(0, count($this->_returnValues) - 1);
            $code    = $this->_returnValues[$n]['code'];
            $message = $this->_returnValues[$n]['message'];
        } else {
            $code    = $this->_options['returnCode'];
            $message = $this->_options['returnMessage'];
        }

        return Payment_Process2_Result::factory('Dummy');
    }

    public function translateAction($action) {
        return false;
    }

    public function getStatus() {
        return false;
    }
}


?>
