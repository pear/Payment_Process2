<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Offline processor
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
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process2
 */


require_once 'Validate/CreditCard.php';

/**
* Payment_Process2_offline
*
* An offline driver that allows you to do offline validation of credit card
* via the Validate_CreditCard package. This package is intended for those
* who wish to someday use a payment gateway, but at this time are not currently
* using one.
*
* @author Joe Stump <joe@joestump.net>
* @package Payment_Process2
*/
class Payment_Process2_offline extends Payment_Process {

  /**
  * $_processed
  *
  * Set to true after the credit card has been processed
  *
  * @author Joe Stump <joe@joestump.net>
  * @var bool $_processed
  */
  var $_processed = false;

  /**
  * $_response
  *
  * The response after the credit card has been processed
  *
  * @author Joe Stump <joe@joestump.net>
  * @var bool $_response
  */
  var $_response  = false;

  /**
  * process
  *
  * Processes the given credit card. Returns PEAR_Error when an error has
  * occurred or it will return a valid Payment_Process2_Result on success.
  *
  * @author Joe Stump <joe@joestump.net>
  * @access public
  * @return mixed
  */
  function process()
  {
    $card = array();
    $card['number'] = $this->cardNumber;
    $card['month']  = $this->expMonth;
    $card['year']   = $this->expYear;

    $check = false;
    switch($this->type)
    {
      case PROCESS_TYPE_VISA:
        $card['type'] = VALIDATE_CREDITCARD_TYPE_VS;
        break;
      case PROCESS_TYPE_MASTERCARD:
        $card['type'] = VALIDATE_CREDITCARD_TYPE_MC;
        break;
      case PROCESS_TYPE_AMEX:
        $card['type'] = VALIDATE_CREDITCARD_TYPE_AX;
        break;
      case PROCESS_TYPE_DISCOVER:
        $card['type'] = VALIDATE_CREDITCARD_TYPE_DS;
        break;
      case PROCESS_TYPE_CHECK:
        return $check = true; // Nothing to process - it's a check
    }

    if (!$check) {
      $this->_result    = Validate_CreditCard::card($card);
      $this->_processed = true;
    }

    if ($this->_result) {
      $code = PROCESS_RESULT_APPROVED;
      $message = 'Valid Credit Card';
    } else {
      $code = PROCESS_RESULT_DECLINED;

      // Run extra checks to get a better error message
      if(Validate_CreditCard::number($card['number'])) {
        $message = 'Card number is invalid';
      } elseif(Validate_CreditCard::expiryDate($card['month'],$card['year'])) {
        $message = 'Invalid expriation date';
      } elseif(Validate_CreditCard::expiryDate($card['number'],$card['type'])) {
        $message = 'Card number does not match specified type';
      }
    }

    if($code == PROCESS_RESULT_DECLINED) {
      throw new Payment_Process2_Exception($message,$code);
    }

    return new Payment_Process2_Result($message,$code);

  }

  /**
  * getStatus
  *
  * Return status or PEAR_Error when it has not been processed yet.
  *
  * @author Joe Stump <joe@joestump.net>
  * @access public
  */
  function getStatus()
  {
    if(!$this->processed) {
      throw new Payment_Process2_Exception('The transaction has not been processed yet.', PROCESS_ERROR_INCOMPLETE);
    }

    return $this->_response;
  }
}

?>
