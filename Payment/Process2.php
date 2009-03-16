<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Main package file
 *
 * Process.php is a unified OOP abstraction layer for credit card and echeck
 * processing gateways (similar to what DB does for database calls).
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
 * @author    Joe Stump <joe@joestump.net>
 * @copyright 1997-2008 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process
 */

/** @todo Get rid of this */
require_once 'PEAR.php';
require_once 'Validate.php';
require_once 'Validate/Finance/CreditCard.php';
require_once 'Payment/Process2/Type.php';
require_once 'Payment/Process2/Exception.php';
require_once 'Payment/Process2/Result.php';


/**
 * Payment_Process
 *
 * @category Payment
 * @package  Payment_Process
 * @author   Ian Eure <ieure@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Payment_Process
 */
class Payment_Process2
{
    /**
     * Error codes
     */
    const ERROR_NOTIMPLEMENTED = -100;
    const ERROR_NOFIELD = -101;
    const ERROR_NOPROCESSOR = -102;
    const ERROR_INCOMPLETE = -1;
    const ERROR_INVALID = -2;
    const ERROR_AVS = -3;
    const ERROR_CVV = -4;
    const ERROR_UNSUPPORTED = -5;
    const ERROR_COMMUNICATION = -200;

    /**
     * Transaction actions
     */
    /**
     * A normal transaction
     */
    const ACTION_NORMAL = 200;

    /**
     * Authorize only. No funds are transferred.
     */
    const ACTION_AUTHONLY = 201;

    /**
     * Credit funds back from a previously-charged transaction.
     */
    const ACTION_CREDIT = 202;

    /**
     * Post-authorize an AUTHONLY transaction.
     */
    const ACTION_POSTAUTH = 203;

    /**
     * Clear a previous transaction
     */
    const ACTION_VOID = 204;

    /**
     * Transaction sources
     */
    const SOURCE_POS = 300;
    const SOURCE_ONLINE = 301;
    const SOURCE_PHONE = 302;
    const SOURCE_MAIL = 303;


    /**
     * Result codes
     */
    const RESULT_APPROVED = 400;
    const RESULT_DECLINED = 401;
    const RESULT_OTHER = 402;
    const RESULT_FRAUD = 403;
    const RESULT_DUPLICATE = 404;
    const RESULT_REVIEW = 405;

    const AVS_MATCH = 500;
    const AVS_MISMATCH = 501;
    const AVS_ERROR = 502;
    const AVS_NOAPPLY = 503;

    const CVV_MATCH = 600;
    const CVV_MISMATCH = 601;
    const CVV_ERROR = 602;
    const CVV_NOAPPLY = 603;

    var $_defaultOptions = array();

    /**
     * Return an instance of a specific processor.
     *
     * @param string $type    Name of the processor
     * @param array  $options Options for the processor
     *
     * @return Payment_Process_Driver Instance of the processor object
     * @throws Payment_Process2_Exception
     */
    function factory($type, $options = array())
    {
        $class = "Payment_Process2_".$type;

        $path = "Payment/Process2/{$type}.php";

        if (@fclose(@fopen($path, 'r', true))) {
            include_once $path;
        }

        if (class_exists($class)) {
            $object =  new $class($options);
            return $object;
        }

        throw new Payment_Process2_Exception('"'.$type.'" processor does not exist',
                                Payment_Process2::ERROR_NOPROCESSOR);
    }

    /**
     * Determine if a field is required.
     *
     * @param string $field Field to check
     *
     * @return boolean true if required, false if optional.
     */
    function isRequired($field)
    {
        return (isset($this->_required[$field]));
    }

    /**
     * Determines if a field exists.
     *
     * @param string $field Field to check
     *
     * @return boolean true if field exists, false otherwise
     * @author Ian Eure <ieure@php.net>
     */
    function fieldExists($field)
    {
        return @in_array($field, $this->getFields());
    }

    /**
     * Get a list of fields.
     *
     * This function returns an array containing all the possible fields which
     * may be set.
     *
     * @author Ian Eure <ieure@php.net>
     * @access public
     * @return array Array of valid fields.
     */
    function getFields()
    {
        $vars = array_keys(get_class_vars(get_class($this)));
        foreach ($vars as $idx => $field) {
            if ($field{0} == '_') {
                unset($vars[$idx]);
            }
        }

        return $vars;
    }

    /**
     * Set class options.
     *
     * @param array $options        Options to set
     * @param array $defaultOptions Default options
     *
     * @return void
     * @author Ian Eure <ieure@php.net>
     */
    function setOptions($options = array(), $defaultOptions = array())
    {
        $defaultOptions = $defaultOptions ? $defaultOptions : $this->_defaultOptions;
        $this->_options = array_merge($defaultOptions, $options);
    }

    /**
     * Get an option value.
     *
     * @param string $option Option to get
     *
     * @return mixed   Option value
     * @access public
     * @author Ian Eure <ieure@php.net>
     */
    function getOption($option)
    {
        return @$this->_options[$option];
    }

    /**
     * Set an option value
     *
     * @param string $option Option name to set
     * @param mixed  $value  Value to set
     *
     * @return void
     * @access public
     * @author Joe Stump <joe@joestump.net>
     */
    function setOption($option,$value)
    {
        return ($this->_options[$option] = $value);
    }

    /**
     * Statically check a Payment_Result class for success
     *
     * @param mixed $obj Object to check
     *
     * @return bool
     * @access public
     * @static
     * @author Joe Stump <joe@joestump.net>
     */
    function isSuccess(Payment_Process2_Result $obj)
    {
        return ($obj->getCode() == Payment_Process2::RESULT_APPROVED);
    }

}

?>