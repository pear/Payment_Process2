<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Represents a credit card type of payment
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
 * @author    Joe Stump <joe@joestump.net>
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process2
 * @see       Validate_Finance_CreditCard
 */

require_once 'Validate/Finance/CreditCard.php';
require_once 'Payment/Process2/Type.php';
require_once 'Payment/Process2/Exception.php';

/**
 * Payment_Process2_Type_CreditCard
 *
 * @category Payment
 * @package  Payment_Process2
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Payment_Process2
 */
class Payment_Process2_Type_CreditCard extends Payment_Process2_Type
{
    /**
     * Self-identifying payment type
     *
     * @var string $_type
     */
    var $_type = 'CreditCard';

    /**
     * Credit card type
     *
     * @var int $type one of Payment_Process2_Type::CC_* constant
     */
    var $type;

    /**
     * Credit card number
     *
     * @var string $cardNumber
     */
    var $cardNumber;

    /**
     * Card Verification Value
     *
     * a.k.a CVV2, CVC, CID
     *
     * @var int $cvv
     */
    var $cvv;

    /**
     * Card expiry date
     *
     * @var string $expDate expiry date in MM/YYYY format
     */
    var $expDate;

    protected $timestamp;

    /**
     * _validateCardNumber
     *
     * Uses Validate_Finance_CreditCard to validate the card number.
     *
     * @author Joe Stump <joe@joestump.net>
     * @return bool
     * @throws Payment_Process2_Exception
     * @see Payment_Process2_Type_CreditCard::_mapType()
     * @see Validate_Finance_CreditCard
     */
    function _validateCardNumber()
    {
        if (!Validate_Finance_CreditCard::number($this->cardNumber, $this->_mapType())) {
            throw new Payment_Process2_Exception('Invalid credit card number');
        }

        return true;
    }

    /**
     * Validates the credit card type
     *
     * Uses Validate_Finance_CreditCard to validate the type.
     *
     * @author Joe Stump <joe@joestump.net>
     * @return bool
     * @throws Payment_Process2_Exception
     * @see Payment_Process2_Type_CreditCard::_mapType()
     * @see Validate_Finance_CreditCard
     */
    function _validateType()
    {
        if (!($type = $this->_mapType())) {
            throw new Payment_Process2_Exception('Credit card type not recognized by in driver');
        }

        if (!Validate_Finance_CreditCard::type($this->cardNumber, $type)) {
            throw new Payment_Process2_Exception('Credit card type not recognized or does not match the card number given');
        }

        return true;
    }

    /**
     * Validates the card verification value
     *
     * @return bool
     * @throws Payment_Process2_Exception
     * @access protected
     */
    function _validateCvv()
    {
        if (strlen($this->cvv) == 0) {
            return true;
        }

        if (!($type = $this->_mapType())) {
            throw new Payment_Process2_Exception('Invalid type map provided in driver');
        }

        if (!Validate_Finance_CreditCard::cvv($this->cvv, $type)) {
            throw new Payment_Process2_Exception('CVV code is invalid or does not match the card type');
        }

        return true;
    }

    /**
     * Validate the card's expiration date.
     *
     * @return bool
     * @throws Payment_Process2_Exception
     * @access protected
     * @author Joe Stump <joe@joestump.net>
     * @todo Fix YxK issues; an expyear of '99' will come up as valid.
     */
    function _validateExpDate()
    {
        @list($month, $year) = explode('/', $this->expDate);
        if (!is_numeric($month) || !is_numeric($year)) {
            throw new Payment_Process2_Exception('Invalid expiration date provided');
        }

        $monthOptions = array('min'     => 1,
                              'max'     => 12,
                              'decimal' => false);

        $date = getdate($this->timestamp);

        $yearOptions = array('min'     => $date['year'],
                             'decimal' => false);

        $validMonth = Validate::number((int)$month, $monthOptions);
        if (!$validMonth) {
            throw new Payment_Process2_Exception('Invalid expiration date provided (month)');
        }


        $validYear = Validate::number((int)$year, $yearOptions);
        if (!$validYear) {
            throw new Payment_Process2_Exception('Invalid expiration date provided (year)');
        }



        if (Validate::number($month, $monthOptions)
            && Validate::number($year, $yearOptions)) {
            if (($month >= $date['mon'] && $year == $date['year'])
                || ($year > $date['year'])) {
                return true;
            }
        }


    }

    /**
     * Maps a Payment_Process2_Type::CC_* constant with a with a value suitable
     * to Validate_Finance_CreditCard package
     *
     * @return string|boolean card type name or FALSE on error
     * @access private
     */
    function _mapType()
    {
        switch ($this->type) {
        case Payment_Process2_Type::CC_MASTERCARD:
            return 'MasterCard';
        case Payment_Process2_Type::CC_VISA:
            return 'Visa';
        case Payment_Process2_Type::CC_AMEX:
            return 'Amex';
        case Payment_Process2_Type::CC_DISCOVER:
            return 'Discover';
        case Payment_Process2_Type::CC_JCB:
            return 'JCB';
        case Payment_Process2_Type::CC_DINERS:
            return 'Diners';
        case Payment_Process2_Type::CC_ENROUTE:
            return 'EnRoute';
        case Payment_Process2_Type::CC_CARTEBLANCHE:
            return 'CarteBlanche';
        default:
            return false;
        }
    }

    /**
     * @param int $timestamp A unix timestamp representing the time.
     */
    public function setDate($timestamp) {
        $this->timestamp = $timestamp;
    }

    /**
     * Class constructor
     *
     * @param int $timestamp A unix timestamp representing the time. If absent, defaults to time()
     */
    public function __construct($timestamp = null) {
        if (empty($timestamp)) {
            $timestamp = time();
        }

        $this->setDate($timestamp);
    }
}

?>