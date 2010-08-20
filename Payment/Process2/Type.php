<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * TrustCommerce processor
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
 * @category   Payment
 * @package    Payment_Process2
 * @author     Joe Stump <joe@joestump.net>
 * @author     Ian Eure <ieure@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Payment_Process2
 */

require_once 'PEAR.php';
require_once 'Validate.php';
require_once 'Payment/Process2/Exception.php';

/**
 * Payment_Process2_Type
 *
 * @author Joe Stump <joe@joestump.net>
 * @category Payment
 * @package Payment_Process2
 * @version @version@
 */
class Payment_Process2_Type
{

    const CC_VISA         = 100;
    const CC_MASTERCARD   = 101;
    const CC_AMEX         = 102;
    const CC_DISCOVER     = 103;
    const CC_JCB          = 104;
    const CC_DINERS       = 105;
    const CC_CARTEBLANCHE = 106;
    const CC_ENROUTE      = 107;

    const CK_SAVINGS  = 1000;
    const CK_CHECKING = 1001;

    // {{{ properties
    /**
     * $_type
     *
     * @var string $type Type of payment (ie. 'CreditCard' or 'eCheck')
     */
    var $_type = null;

    /**
     * $firstName
     *
     * @var string $firstName
     */
    var $firstName;

    /**
     * $lastName
     *
     * @var string $lastName
     */
    var $lastName;

    /**
     * $company
     *
     * @var string $company
     */
    var $company;

    /**
     * $address
     *
     * @var string $addres
     */
    var $address;

    /**
     * $city
     *
     * @var string $city
     */
    var $city;

    /**
     * $state
     *
     * @var string $state State/Province of customer
     */
    var $state;

    /**
     * $zip
     *
     * @var string $zip Zip/Postal code of customer
     */
    var $zip;

    /**
     * $country
     *
     * @var string $country Country code of customer (ie. US)
     */
    var $country;

    /**
     * $phone
     *
     * @var string $phone Phone number of customer
     */
    var $phone;

    /**
     * $fax
     *
     * @var string $fax Fax number of customer
     */
    var $fax;

    /**
     * $city
     *
     * @var string $email Email address of customer
     */
    var $email;

    /**
     * $ipAddress
     *
     * @var string $ipAddress Remote IP address of customer
     */
    var $ipAddress;
    // }}}

    // {{{ &factory($type)
    /**
    * factory
    *
    * Creates and returns an instance of a payment type.
    *
    * @param string $type
    * @return Payment_Process2_Type
    * @throws Payment_Process2_Exception
    */
    public static function factory($type, $options = array())
    {
        $class = "Payment_Process2_Type_$type";
        $path = "Payment/Process2/Type/". basename($type) .".php";

        // If the class does not exist, attempt to include it
        if (!class_exists($class)) {
        	foreach (explode(PATH_SEPARATOR, get_include_path()) as $includePath) {
        		if (is_readable("$includePath/$path")) {
            		include_once $path;
            		break;
        		}
        	}
        }

        if (class_exists($class)) {
            $instance = new $class($options);
            return $instance;
        }

        throw new Payment_Process2_Exception('"'.$type.'" processor does not exist',
                                Payment_Process2::ERROR_NOPROCESSOR);
    }

    // }}}

    // {{{ validate()
    /**
    * Validate this object
    *
    * @access public
    * @return bool
    * @throws Payment_Process2_Exception
    * @todo validate() to raise exceptions
    */
    function validate()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $validate => $value) {
            $method = '_validate'.ucfirst($validate);
            if (method_exists($this, $method)) {
                $this->$method();
            }
        }

        return true;
    }
    // }}}

    // {{{ getType()
    /**
    * getType
    *
    * @author Joe Stump <joe@joestump.net>
    * @access public
    * @return string
    */
    function getType()
    {
      return $this->_type;
    }
    // }}}
    // {{{ _validateEmail()
    /**
     * Validate an email address.
     *
     * @author Ian Eure <ieure@php.net>
     * @access private
     * @return boolean true on success, false on failure.
     */
    function _validateEmail()
    {
        if (isset($this->email) && strlen($this->email)) {
            if (!Validate::email($this->email, false)) {
                throw new Payment_Process2_Exception("Invalid email address");
            }
        }

        return true;
    }
    // }}}
    // {{{ _validateZip()
    /**
     * Validate the zip code.
     *
     * This only validates U.S. zipcodes; country must be set to 'us' for zip to
     * be validated.
     *
     * @author Ian Eure <ieure@php.net>
     * @access private
     * @return boolean true on success, false otherwise
     * @todo use Validate_*::postalCode() method
     */
    function _validateZip()
    {
        if (isset($this->zip) && strtolower($this->country) == 'us') {
            if (!preg_match('/^[0-9]{5}(-[0-9]{4})?$/', $this->zip)) {
                throw new Payment_Process2_Exception("Invalid email address");
            }
        }

        return true;
    }
    // }}}
}

?>
