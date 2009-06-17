<?php
require_once 'Payment/Process2/Result.php';
require_once 'Payment/Process2/Result/Driver.php';

/**
 * Dummy response
 *
 * @category Payment
 * @package  Payment_Process2
 * @author   Ian Eure <ieure@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Payment_Process2
 */
class Payment_Process2_Result_Dummy extends Payment_Process2_Result implements Payment_Process2_Result_Driver
{

    public function parse() {}

}