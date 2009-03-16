<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Holds code shared between all processors
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
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_Process
 */

require_once 'Payment/Process2.php';
require_once 'Payment/Process2/Type.php';
require_once 'HTTP/Request2.php';

/**
 * Base class for processor
 *
 * @category  Payment
 * @package   Payment_Process
 * @author    Ian Eure <ieure@php.net>
 * @author    Joe Stump <joe@joestump.net>
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Payment_Process
 * @abstract
 */
class Payment_Process2_Common
{
    // {{{ Private Properties
    /**
     * Options.
     *
     * @var array
     * @see setOptions()
     * @access private;
     */
    var $_options = array();

    /**
     * Array of fields which are required.
     *
     * @var array
     * @access private
     * @see _makeRequired()
     */
    var $_required = array();

    /**
     * Processor-specific data.
     *
     * @access private
     * @var array
     */
    var $_data = array();

    /**
     * $_driver
     *
     * @author Joe Stump <joe@joestump.net>
     * @var string $_driver
     * @access private
     */
    var $_driver = null;

    /**
     * PEAR::Log instance
     *
     * @var     object
     * @access  protected
     * @see     Log
     */
    var $_log;

    /**
     * Mapping between API fields and processors'
     *
     * @var mixed $_typeFieldMap
     * @access protected
     */
    var $_typeFieldMap = array();

    /**
     * Reference to payment type
     *
     * An internal reference to the Payment_Process2_Type that is currently
     * being processed.
     *
     * @var mixed $_payment Instance of Payment_Type
     * @access protected
     * @see Payment_Process2_Common::setPayment()
     */
    var $_payment = null;
    // }}}
    // {{{ Public Properties
    /**
     * Your login name to use for authentication to the online processor.
     *
     * @var string
     */
    var $login = '';

    /**
     * Your password to use for authentication to the online processor.
     *
     * @var string
     */
    var $password = '';

    /**
     * Processing action.
     *
     * This should be set to one of the Payment_Process2::ACTION_* constants.
     *
     * @var int
     */
    var $action = '';

    /**
     * A description of the transaction (used by some processors to send
     * information to the client, normally not a required field).
     * @var string
     */
    var $description = '';

    /**
     * The transaction amount.
     *
     * @var double
     */
    var $amount = 0;

    /**
     * An invoice number.
     *
     * @var mixed string or int
     */
    var $invoiceNumber = '';

    /**
     * Customer identifier
     *
     * @var mixed string or int
     */
    var $customerId = '';



    /**
     * Transaction source.
     *
     * This should be set to one of the Payment_Process2::SOURCE_* constants.
     *
     * @var int
     */
    var $transactionSource;
    // }}}

    var $_defaultOptions = array();


    var $_request = null;

    // {{{ __construct($options = false)
    /**
     * __construct
     *
     * PHP 5.x constructor
     *
     * @param array $options Options
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     */
    function __construct($options = array(), HTTP_Request2 $request = null)
    {
        $this->setOptions($options);
        $this->setRequest($request);
    }
    // }}}

    function setRequest(HTTP_Request2 $request = null) {
        if (empty($request)) {
            $request = new HTTP_Request2();
        }
        $this->_request = $request;
    }

    // {{{ setPayment($payment)
    /**
     * Sets payment
     *
     * Returns false if payment could not be set. This usually means the
     * payment type is not valid  or that the payment type is valid, but did
     * not validate. It could also mean that the payment type is not supported
     * by the given processor.
     *
     * @param mixed $payment Object of Payment_Process2_Type
     *
     * @return bool
     * @access public
     * @author Joe Stump <joe@joestump.net>
     */
    function setPayment(Payment_Process2_Type $payment)
    {
        if (isset($this->_typeFieldMap[$payment->getType()]) &&
            is_array($this->_typeFieldMap[$payment->getType()])) {

            Payment_Process2_Type::isValid($payment);

            $this->_payment = $payment;
            // Map over the payment specific fields. Check out
            // $_typeFieldMap for more information.
            $paymentType = $payment->getType();
            foreach ($this->_typeFieldMap[$paymentType] as $generic => $specific) {

                $func = '_handle'.ucfirst($generic);
                if (method_exists($this, $func)) {
                    $this->$func();
                } else {
                    // TODO This may screw things up - the problem is that
                    // CC information is no longer member variables, so we
                    // can't overwrite it. You could always handle this
                    // with a _handle funciton. I don't think it will cause
                    // problems, but it could.
                    if (!isset($this->_data[$specific])) {
                        if (isset($this->_payment->$generic)) {
                            $this->_data[$specific] = $this->_payment->$generic;
                        }
                    }
                }
            }

            return true;
        }

        throw new Payment_Process2_Exception('Invalid type field map');
    }
    // }}}
    // {{{ setFrom($where)
    /**
     * Set many fields.
     *
     * @param array $where Associative array of data to set, in the format
     *                     'field' => 'value',
     *
     * @return void
     */
    function setFrom($where)
    {
        foreach ($this->getFields() as $field) {
            if (isset($where[$field])) {
                $this->$field = $where[$field];
            }
        }
    }
    // }}}

    // {{{ validate()
    /**
     * validate
     *
     * Validates data before processing. This function may be overloaded by
     * the processor.
     *
     * @return boolean true if validation succeeded, PEAR_Error if it failed.
     */
    function validate()
    {
        foreach ($this->getFields() as $field) {
            $func = '_validate'.ucfirst($field);

            // Don't validate unset optional fields
            if (!$this->isRequired($field) && !empty($this->$field)) {
                continue;
            }

            if (method_exists($this, $func)) {
                $res = $this->$func();
                /** @todo All of these should raise exceptions instead */
                if (is_bool($res) && $res == false) {
                    throw new Payment_Process2_Exception('Validation of field "'.$field.'" failed; the method should have raised an exception.', Payment_Process2::ERROR_INVALID);
                }
            }
        }

        if ($this->_payment instanceof Payment_Process2_Type) {
            Payment_Process2_Type::isValid($this->_payment);
        } else {
            throw new Payment_Process2_Exception("Payment type not set");
        }

        return true;
    }
    // }}}
    // {{{ set($field, $value)
    /**
     * Set a value.
     *
     * This will set a value, such as the credit card number. If the requested
     * field is not part of the basic set of supported fields, it is set in
     * $_options.
     *
     * @param string $field The field to set
     * @param string $value The value to set
     *
     * @return void
     */
    function set($field, $value)
    {
        if (!$this->fieldExists($field)) {
            throw new Payment_Process2_Exception('Field "' . $field . '" does not exist.', Payment_Process2::ERROR_INVALID);
        }
        $this->$field = $value;
        return true;
    }
    // }}}
    // {{{ isRequired($field)
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
    // }}}
    // {{{ fieldExists($field)
    /**
     * Determines if a field exists.
     *
     * @param string $field Field to check
     *
     * @return boolean TRUE if field exists, FALSE otherwise
     * @author Ian Eure <ieure@php.net>
     */
    function fieldExists($field)
    {
        return @in_array($field, $this->getFields());
    }
    // }}}
    // {{{ getFields()
    /**
     * Get a list of fields.
     *
     * This function returns an array containing all the possible fields which
     * may be set.
     *
     * @return array Array of valid fields.
     * @author Ian Eure <ieure@php.net>
     * @access public
     * @todo    Redo with reflection perhaps?
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
    // }}}
    // {{{ setOptions($options = array(), $defaultOptions = array())
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
        if (!is_array($options)) {
            throw new InvalidArgumentException("Must provide an array");
        }
        $defaultOptions = $defaultOptions ? $defaultOptions : $this->_defaultOptions;
        $this->_options = array_merge($defaultOptions, $options);
    }
    // }}}
    // {{{ getOption($option)
    /**
     * Get an option value.
     *
     * @param string $option Option to get
     *
     * @return mixed  Option value
     * @author Ian Eure <ieure@php.net>
     */
    function getOption($option)
    {
        return @$this->_options[$option];
    }
    // }}}
    // {{{ setOption($option,$value)
    /**
     * Set an option value
     *
     * @param string $option Option name to set
     * @param mixed  $value  Value to set
     *
     * @return mixed
     * @author Joe Stump <joe@joestump.net>
     * @access public
     */
    function setOption($option,$value)
    {
        return ($this->_options[$option] = $value);
    }
    // }}}

    // {{{ _isDefinedConstant($value, $class)
    /**
     * See if a value is a defined constant.
     *
     * This function checks to see if $value is defined in one of
     * Payment_Process2::{$class}_*. It's used to verify that e.g.
     * $object->action is one of Payment_Process2::ACTION_NORMAL,
     * Payment_Process2::ACTION_AUTHONLY etc.
     *
     * @param mixed $value Value to check
     * @param mixed $class Constant class to check
     *
     * @return boolean TRUE if it is defined, FALSE otherwise.
     * @access private
     */
    function _isDefinedConst($value, $class)
    {
        $constClass = 'Payment_Process2::'.strtoupper($class).'_';

        $length = strlen($constClass);
        $consts = get_defined_constants();
        $found  = false;
        foreach ($consts as $constant => $constVal) {
            if (strncmp($constClass, $constant, $length) === 0 &&
                $constVal == $value) {
                $found = true;
                break;
            }
        }

        return $found;
    }
    // }}}
    // {{{ _makeRequired()
    /**
     * Mark a field (or fields) as being required.
     *
     * @param string $field Field name
     * @param string ...
     *
     * @return boolean always true.
     */
    function _makeRequired()
    {
        foreach (func_get_args() as $field) {
            $this->_required[$field] = true;
        }
        return true;
    }
    // }}}
    // {{{ _makeOptional()
    /**
     * Mark a field as being optional.
     *
     * @param string $field Field name
     * @param string ...
     *
     * @return boolean always TRUE.
     */
    function _makeOptional()
    {
        foreach (func_get_args() as $field) {
            unset($this->_required[$field]);
        }
        return true;
    }
    // }}}
    // {{{ _validateType()
    /**
     * Validates transaction type.
     *
     * @return bool
     * @throws Payment_Process2_Exception
     * @access private
     */
    function _validateType()
    {
        if (!$this->_isDefinedConst($this->type, 'type')) {
            throw new Payment_Process2_Exception("Invalid type");
        }

        return true;
    }
    // }}}
    // {{{ _validateAction()
    /**
     * Validates transaction action.
     *
     * @return bool
     * @throws Payment_Process2_Exception
     * @access private
     */
    function _validateAction()
    {
        if (!$this->translateAction($this->action) !== false) {
            throw new Payment_Process2_Exception("Invalid action");
        }

        return true;
    }
    // }}}

    // {{{ _validateSource()
    /**
     * Validates transaction source.
     *
     * @return bool
     * @throws Payment_Process2_Exception
     * @access private
     */
    function _validateSource()
    {
        if (!$this->_isDefinedConst($this->transactionSource, 'source')) {
            throw new Payment_Process2_Exception("Invalid source");
        }

        return true;
    }
    // }}}
    // {{{ _validateAmount()
    /**
     * Validates the charge amount.
     *
     * Charge amount must be 8 characters long, double-precision.
     * Current min/max are rather arbitrarily set to $0.99 and $99999.99,
     * respectively.
     *
     * @return bool
     * @throws Payment_Process2_Exception
     */
    function _validateAmount()
    {
        $result = Validate::number($this->amount, array(
            'decimal' => '.',
            'dec_prec' => 2,
            'min' => 0.99,
            'max' => 99999.99
        ));

        if (!$result) {
            throw new Payment_Process2_Exception("Invalid amount");
        }

        return true;
    }
    // }}}
    // {{{ _handleAction()
    /**
     * Handles action
     *
     * Actions are defined in translateAction() and then
     * handled here. We may decide to abstract the defines in the driver.
     *
     * @return void
     * @access private
     */
    function _handleAction()
    {
        $this->_data[$this->_fieldMap['action']] = $this->translateAction($this->action);
    }
    // }}}
    // {{{ _prepare()
    /**
     * Prepares the POST data.
     *
     * This function handles translating the data set in the front-end to the
     * format needed by the back-end. The prepared data is stored in
     * $this->_data. If a '_handleField' method exists in this class (e.g.
     * '_handleCardNumber()'), that function is called and /must/ set
     * $this->_data correctly. If no field-handler function exists, the data
     * from the front-end is mapped into $_data using $this->_fieldMap.
     *
     * @return array Data to POST
     * @access private
     */
    function _prepare()
    {
        /**
         * FIXME - because this only loops through stuff in the fieldMap, we
         *         can't have handlers for stuff which isn't specified in there.
         *         But the whole point of having a _handler() is that you need
         *         to do something more than simple mapping.
         */
        foreach ($this->_fieldMap as $generic => $specific) {
            $func = '_handle'.ucfirst($generic);
            if (method_exists($this, $func)) {
                $this->$func();
            } else {
                /**
                 * @todo This may screw things up - the problem is that
                 *       CC information is no longer member variables, so we
                 *       can't overwrite it. You could always handle this with
                 *       a _handle funciton. I don't think it will cause problems,
                 *       but it could.
                 */
                if (!isset($this->_data[$specific])) {
                    if (isset($this->$generic)) {
                        $this->_data[$specific] = $this->$generic;
                    }

                    // Form of payments data overrides those set in the
                    // Payment_Process2_Common.
                    if (isset($this->_payment->$generic)) {
                        $this->_data[$specific] = $this->_payment->$generic;
                    }
                }
            }
        }

        return true;
    }
    // }}}
}

?>
